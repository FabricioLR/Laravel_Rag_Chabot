<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\HelpRequest;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('help');
    }

    public function contact(Request $request): RedirectResponse
    {
        try{
            $validated = $request->validate([
                'name'         => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'description'  => 'required|string|max:255',
            ]);

            HelpRequest::create($validated);

            return redirect()->back()->with('success', 'Sua mensagem foi enviada com sucesso!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['error' => "Cannot send contact message. Try again later."]);
        }
    }
}