<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the logged-in employee's information.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->load(['employee.office', 'employee.division']);
        $employee = $user->employee;

        return view('employee.profile', compact('user', 'employee'));
    }

    /**
     * Update the user's e-signature.
     */
    public function updateSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:2048', // max 2MB
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->hasFile('signature')) {
            // Delete old signature if it exists
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }

            // Store new signature
            $file = $request->file('signature');
            $path = $file->storeAs(
                'signatures',
                $user->id . '_' . time() . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $user->signature_path = $path;
            $user->save();
        }

        return back()->with('status', 'E-Signature updated successfully.');
    }
}
