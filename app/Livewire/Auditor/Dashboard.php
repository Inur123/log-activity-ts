<?php

namespace App\Livewire\Auditor;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.auditor')] // layout khusus halaman setelah login (bukan guest)
#[Title('Auditor Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.auditor.dashboard');
    }
}
