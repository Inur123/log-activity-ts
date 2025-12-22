<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';

    protected function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ];
    }

    protected array $messages = [
        'email.required'    => 'Email harus diisi',
        'email.email'       => 'Format email tidak valid',
        'password.required' => 'Password harus diisi',
        'password.min'      => 'Password minimal 6 karakter',
    ];

    public function login()
    {
        $this->validate();

        $user = User::query()->where('email', $this->email)->first();

        if ($user && !$user->is_active) {
            $this->dispatch('flash', [
                'type' => 'error',
                'message' => 'Akun Anda belum diaktifkan. Silakan hubungi admin untuk aktivasi akun.'
            ]);
            return;
        }

        $ok = Auth::attempt([
            'email'     => $this->email,
            'password'  => $this->password,
            'is_active' => true,
        ]);

        if (!$ok) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        session()->regenerate();

        $user = Auth::user();

        return match ($user->role) {
            'super_admin' => $this->redirect(route('super_admin.dashboard'), navigate: true),
            'auditor'     => $this->redirect(route('auditor.dashboard'), navigate: true),
            default       => $this->redirect(route('login'), navigate: true),
        };
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
