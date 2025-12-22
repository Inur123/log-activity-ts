<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.super-admin')] // layout khusus halaman setelah login (bukan guest)
#[Title('Super Admin Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.super-admin.dashboard');
    }
}
