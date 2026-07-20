<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Attendance;
use App\Helpers\GeofenceHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Awal (Global)
        $request->validate([
            'action_type' => 'required|in:CHECK_IN,CHECK_OUT',
            'attendance_type' => 'required|in:WFO,WFH,IZIN,SAKIT',
            'device_id' => 'required|string',
        ]);

        $user = Auth::user();

        // 2. Proteksi Device Binding
        if ($user->device_id !== $request->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Perangkat ini tidak cocok dengan akun Anda!'
            ], 403);
        }

        // 3. Validasi Khusus Berdasarkan Action (CHECK_IN vs CHECK_OUT)
        if ($request->action_type === 'CHECK_OUT' && $request->attendance_type === 'WFO') {
            $request->validate([
                'notes' => 'required|string|min:10', // Minimal 10 karakter untuk laporan
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Bukti foto maks 2MB
            ]);
        }

        // 4. Jalankan Mesin Geofencing HANYA jika tipenya WFO
        $locationId = null;
        $userLat = null;
        $userLng = null;
        $status = 'SUCCESS';

        if ($request->attendance_type === 'WFO') {
            // Validasi tambahan khusus WFO
            $request->validate([
                'location_id' => 'required|exists:locations,id',
                'user_latitude' => 'required|numeric',
                'user_longitude' => 'required|numeric',
                'is_mock_location' => 'required|boolean',
            ]);

            // Proteksi Anti-Fake GPS
            if ($request->is_mock_location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecurangan terdeteksi! Matikan aplikasi Fake GPS Anda.'
                ], 400);
            }

            $location = Location::findOrFail($request->location_id);
            $locationId = $location->id;
            $userLat = $request->user_latitude;
            $userLng = $request->user_longitude;
            $isValidArea = false;

            if ($location->type === 'Radius') {
                $isValidArea = GeofenceHelper::checkRadius(
                    $userLat, $userLng, $location->latitude, $location->longitude, $location->radius_meter
                );
            } elseif ($location->type === 'Mapping') {
                $vertices = is_string($location->polygon_coords) 
                    ? json_decode($location->polygon_coords, true) 
                    : $location->polygon_coords;

                $isValidArea = GeofenceHelper::checkMapping($userLat, $userLng, $vertices);
            }

            $status = $isValidArea ? 'SUCCESS' : 'FAILED_OUTSIDE';
        } else {
            // Jika WFH/Izin/Sakit, statusnya bisa otomatis SUCCESS atau PENDING (butuh approval mentor)
            $status = in_array($request->attendance_type, ['IZIN', 'SAKIT']) ? 'PENDING_APPROVAL' : 'SUCCESS';
        }

        // 5. Handle Upload Foto (Khusus CHECK_OUT WFO)
        $attachmentPath = null;
        if ($request->hasFile('image')) {
            // Simpan foto ke dalam folder storage/app/public/attendances
            $attachmentPath = $request->file('image')->store('attendances', 'public');
        }

        // 6. Simpan data absensi
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'location_id' => $locationId,
            'action_type' => $request->action_type,
            'attendance_type' => $request->attendance_type,
            'user_latitude' => $userLat,
            'user_longitude' => $userLng,
            'status' => $status,
            'is_mock_location' => $request->is_mock_location ?? false,
            'device_id' => $request->device_id,
            'notes' => $request->notes ?? ($request->attendance_type !== 'WFO' ? 'Absen ' . $request->attendance_type : null),
            'attachment_path' => $attachmentPath,
        ]);

        // 7. Response handling untuk WFO yang di luar area
        if ($request->attendance_type === 'WFO' && !$isValidArea) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar jangkauan wilayah kantor!',
                'data' => $attendance
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absen ' . $request->action_type . ' (' . $request->attendance_type . ') berhasil dicatat!',
            'data' => $attendance
        ], 200);
    }

    public function recap(Request $request)
    {
        // 1. Ambil parameter bulan dan tahun dari request (default: bulan & tahun saat ini)
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        
        $user = Auth::user();

        // 2. Cari tanggal awal dan akhir dari bulan yang dipilih
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        $totalDays = $endDate->day; // Mendapatkan jumlah hari (28, 29, 30, atau 31)

        // 3. Ambil data absensi user yang riil pada rentang bulan tersebut
        $realAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->get()
            ->groupBy(function($date) {
                // Group berdasarkan tanggal (YYYY-MM-DD) agar mudah dipetakan
                return Carbon::parse($date->created_at)->format('Y-m-d');
            });

        // 4. Lakukan looping untuk men-generate data FULL satu bulan kalender
        $recapData = [];

        for ($day = 1; $day <= $totalDays; $day++) {
            // Format tanggal saat ini dalam loop (misal: 2026-07-01)
            $currentDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            
            // Cek apakah ada data absen riil di tanggal ini
            if ($realAttendances->has($currentDate)) {
                $dayLogs = $realAttendances->get($currentDate);
                
                // Cari data CHECK_IN dan CHECK_OUT untuk tanggal tersebut
                $checkIn = $dayLogs->firstWhere('action_type', 'CHECK_IN');
                $checkOut = $dayLogs->firstWhere('action_type', 'CHECK_OUT');

                $recapData[] = [
                    'date' => $currentDate,
                    'day_name' => Carbon::parse($currentDate)->translatedFormat('l'), // Nama hari (Senin, Selasa, dst)
                    'has_attendance' => true,
                    'check_in' => $checkIn ? [
                        'id' => $checkIn->id,
                        'time' => Carbon::parse($checkIn->created_at)->format('H:i:s'),
                        'attendance_type' => $checkIn->attendance_type,
                        'status' => $checkIn->status,
                    ] : null,
                    'check_out' => $checkOut ? [
                        'id' => $checkOut->id,
                        'time' => Carbon::parse($checkOut->created_at)->format('H:i:s'),
                        'attendance_type' => $checkOut->attendance_type,
                        'status' => $checkOut->status,
                        'notes' => $checkOut->notes,
                        'attachment_url' => $checkOut->attachment_path ? asset('storage/' . $checkOut->attachment_path) : null,
                    ] : null
                ];
            } else {
                // Jika tidak ada absen (atau hari belum berjalan), kirim data null terstruktur
                $recapData[] = [
                    'date' => $currentDate,
                    'day_name' => Carbon::parse($currentDate)->translatedFormat('l'),
                    'has_attendance' => false,
                    'check_in' => null,
                    'check_out' => null
                ];
            }
        }

        // 5. Return response rekap bulanan
        return response()->json([
            'success' => true,
            'message' => 'Rekap absensi periode ' . $startDate->format('F Y') . ' berhasil diambil.',
            'meta' => [
                'month' => (int)$month,
                'year' => (int)$year,
                'total_days' => $totalDays,
            ],
            'data' => $recapData
        ], 200);
    }
}