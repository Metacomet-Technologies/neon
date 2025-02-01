<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NativeCommand;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class NativeCommandController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/NativeCommand/Index', [
            'nativeCommands' => NativeCommand::paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jobs = collect(scandir(app_path('Jobs')))
            ->filter(function ($file) {
                return ! in_array($file, ['.', '..', 'Job.php']);
            })
            ->map(function ($file) {
                return str_replace('.php', '', $file);
            });

        return Inertia::render('Admin/NativeCommand/Create', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(NativeCommand $nativeCommand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NativeCommand $nativeCommand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NativeCommand $nativeCommand)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NativeCommand $nativeCommand)
    {
        //
    }
}
