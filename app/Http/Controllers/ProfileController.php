<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Profile;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        // Retrieve the profile associated with the authenticated user or create a new one
        $profile = auth()->user()->profile ?? new Profile();

        return view('profile.edit', [
            'user' => $request->user(),
            'profile' => $profile,
        ]);
    }

    /**
     * Update the user's profile information and user data.
     */
    public function update(Request $request): RedirectResponse
    {
        // Validate the input data, including optional avatar upload
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate avatar as an image file
            'email' => 'required|string|email|max:255', // Ensure email is also validated
        ]);

        // Update the user information
        $user = $request->user();
        $user->fill($request->only('email'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Set the updated_by field to the current user's ID
        $user->updated_by = $request->user()->id;

        $user->save();

        // Retrieve the profile associated with the authenticated user or create a new one
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        // Set the created_by field for new profiles
        if ($profile->exists === false) {
            $profile->created_by = $user->id;
        }

        // Handle the avatar upload if a file is provided
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public'); // Store the avatar in 'public/avatars'
            $profile->avatar = $avatarPath;
        }

        // Fill the profile with the validated data, excluding the avatar and email
        $profile->fill($request->except(['avatar', 'email']));
        // Set the updated_by field for the profile
        $profile->updated_by = $user->id;

        $profile->save(); // Save the profile to the database

        // Redirect back to the profile edit page with a success message
        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
