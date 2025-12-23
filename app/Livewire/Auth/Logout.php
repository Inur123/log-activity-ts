<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Logout extends Component
{
    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // toast setelah redirect -> session flash
        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Berhasil logout.',
        ]);

        return $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.logout');
    }
}
