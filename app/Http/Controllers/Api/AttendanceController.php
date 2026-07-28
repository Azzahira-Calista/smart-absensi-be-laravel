<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceProof;
use App\Models\Location;
use App\Helpers\GeofenceHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // 1. ENDPOINT UPLOAD BUKTI (Berdasarkan Tanggal Absen)
    public function uploadBukti(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'notes' => 'required|string|min:10',
            'attachment' => 'nullable|file|max:1024', // max 1 MB
        ]);

        $user = Auth::user();
        $path = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attendances', 'public');
        }

        // Update jika sudah ada bukti di tanggal tersebut, atau create baru jika belum ada
        $proof = AttendanceProof::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $request->date,
            ],
            [
                'notes' => $request->notes,
                'attachment_path' => $path ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bukti berhasil diunggah.',
            'data' => [
                'id' => $proof->id,
                'date' => $proof->date,
                'notes' => $proof->notes,
                'attachment_path' => $proof->attachment_path,
                'attachment_url' => $proof->attachment_path ? asset('storage/' . $proof->attachment_path) : null,
            ]
        ], 200);
    }

    public function bukti(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $proof = AttendanceProof::where('user_id', Auth::id())
            ->whereDate('date', $request->date)
            ->first();

        if (!$proof) {
            return response()->json([
                'success' => false,
                'message' => 'Bukti tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bukti berhasil diambil.',
            'data' => [
                'date' => $proof->date,
                'notes' => $proof->notes,
                'attachment_url' => $proof->attachment_path
                    ? asset('storage/' . $proof->attachment_path)
                    : null,
            ]
        ]);
    }

    // 2. ENDPOINT ABSENSI (CHECK-IN / CHECK-OUT)
    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'action_type' => 'required|in:CHECK_IN,CHECK_OUT',
            'attendance_type' => 'required|in:WFO,WFH,IZIN,SAKIT',
            'device_id' => 'required|string',
        ]);

        $user = Auth::user();

        // Proteksi Device Binding
        if ($user->device_id !== $request->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Perangkat ini tidak cocok dengan akun Anda!'
            ], 403);
        }

        // Geofencing WFO
        $userLat = null;
        $userLng = null;
        $status = 'SUCCESS';

        if ($request->attendance_type === 'WFO') {
            $request->validate([
                'user_latitude' => 'required|numeric',
                'user_longitude' => 'required|numeric',
            ]);

            $userLat = $request->user_latitude;
            $userLng = $request->user_longitude;

            $location = Location::first(); 

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi kantor belum dikonfigurasi di server.'
                ], 500);
            }

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
            $status = in_array($request->attendance_type, ['IZIN', 'SAKIT']) ? 'PENDING_APPROVAL' : 'SUCCESS';
        }

        // Simpan Absen
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'action_type' => $request->action_type,
            'attendance_type' => $request->attendance_type,
            'user_latitude' => $userLat,
            'user_longitude' => $userLng,
            'status' => $status,
            'device_id' => $request->device_id,
        ]);

        if ($request->attendance_type === 'WFO' && !$isValidArea) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar jangkauan wilayah kantor!',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absen ' . $request->action_type . ' (' . $request->attendance_type . ') berhasil dicatat!',
        ], 200);
    }
    public function today()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();

        $checkIn = $attendances->firstWhere('action_type', 'CHECK_IN');
        $checkOut = $attendances->firstWhere('action_type', 'CHECK_OUT');

        // Ambil bukti untuk hari ini (jika ada)
        // $proof = AttendanceProof::where('user_id', $user->id)
        //     ->where('date', $today)
        //     ->first();

        return response()->json([
            'success' => true,
            'message' => 'Data presensi hari ini berhasil diambil.',
            'data' => [
                'date' => $today,
                'check_in' => $checkIn ? [
                    'id' => $checkIn->id,
                    'time' => Carbon::parse($checkIn->created_at)->format('H:i:s'),
                    'attendance_type' => $checkIn->attendance_type,
                    'status' => $checkIn->status,
                    'user_latitude' => $checkIn->user_latitude,
                    'user_longitude' => $checkIn->user_longitude,

                ] : null,
                'check_out' => $checkOut ? [
                    'id' => $checkOut->id,
                    'time' => Carbon::parse($checkOut->created_at)->format('H:i:s'),
                    'attendance_type' => $checkOut->attendance_type,
                    'status' => $checkOut->status,
                    'user_latitude' => $checkOut->user_latitude,
                    'user_longitude' => $checkOut->user_longitude,
                ] : null,
                // 'proof' => $proof ? [
                //     'notes' => $proof->notes,
                //     'attachment_path' => $proof->attachment_path,
                //     'attachment_url' => $proof->attachment_path ? asset('storage/' . $proof->attachment_path) : null,
                // ] : null,
            ]
        ], 200);
    }

    public function recap(Request $request)
    {
        Carbon::setLocale('id');

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);
        $user = Auth::user();

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $totalDays = $endDate->day;

        // Ambil semua log absensi dalam 1 bulan
        $realAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->created_at)->format('Y-m-d');
            });

        // Ambil semua data bukti dalam 1 bulan sekaligus (biar efisien/ga query berulang kali di loop)
        // $proofs = AttendanceProof::where('user_id', $user->id)
        //     ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
        //     ->get()
        //     ->keyBy('date');

        $recapData = [];

        for ($day = 1; $day <= $totalDays; $day++) {
            $dateObj = Carbon::createFromDate($year, $month, $day);

            if ($dateObj->isWeekend()) {
                continue;
            }

            $currentDate = $dateObj->format('Y-m-d');
            // $dayProof = $proofs->get($currentDate);

            if ($realAttendances->has($currentDate)) {
                $dayLogs = $realAttendances->get($currentDate);
                $checkIn = $dayLogs->firstWhere('action_type', 'CHECK_IN');
                $checkOut = $dayLogs->firstWhere('action_type', 'CHECK_OUT');

                $recapData[] = [
                    'date' => $currentDate,
                    'day_name' => $dateObj->translatedFormat('l'),
                    'has_attendance' => true,
                    'check_in' => $checkIn ? [
                        'id' => $checkIn->id,
                        'time' => Carbon::parse($checkIn->created_at)->format('H:i:s'),
                        'attendance_type' => $checkIn->attendance_type,
                        'status' => $checkIn->status,
                        'user_latitude' => $checkIn->user_latitude,
                        'user_longitude' => $checkIn->user_longitude,
                    ] : null,
                    'check_out' => $checkOut ? [
                        'id' => $checkOut->id,
                        'time' => Carbon::parse($checkOut->created_at)->format('H:i:s'),
                        'attendance_type' => $checkOut->attendance_type,
                        'status' => $checkOut->status,
                        'user_latitude' => $checkOut->user_latitude,
                        'user_longitude' => $checkOut->user_longitude,
                    ] : null,
                    // 'proof' => $dayProof ? [
                    //     'notes' => $dayProof->notes,
                    //     'attachment_path' => $dayProof->attachment_path,
                    //     'attachment_url' => $dayProof->attachment_path ? asset('storage/' . $dayProof->attachment_path) : null,
                    // ] : null,
                ];
            } else {
                $recapData[] = [
                    'date' => $currentDate,
                    'day_name' => $dateObj->translatedFormat('l'),
                    'has_attendance' => false,
                    'check_in' => null,
                    'check_out' => null,
                    // 'proof' => $dayProof ? [
                    //     'notes' => $dayProof->notes,
                    //     'attachment_path' => $dayProof->attachment_path,
                    //     'attachment_url' => $dayProof->attachment_path ? asset('storage/' . $dayProof->attachment_path) : null,
                    // ] : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekap absensi periode ' . $startDate->translatedFormat('F Y') . ' berhasil diambil.',
            'data' => $recapData
        ], 200);
    }
}