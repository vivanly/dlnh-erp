<?php

namespace App\Http\Controllers;

use App\Models\Ppcb;
use Illuminate\Http\Request;

class PpcbController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ppcbs = Ppcb::all();
        return view('ppcb.index', compact('ppcbs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ppcb.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ma' => 'required|unique:ppcb,ma',
            'ten_ppcb' => 'required',
        ]);

        Ppcb::create($request->all());

        return redirect()->route('ppcb.index')->with('success', 'Thêm mới thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ppcb = Ppcb::findOrFail($id);
        return view('ppcb.show', compact('ppcb'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $ppcb = Ppcb::findOrFail($id);
        return view('ppcb.edit', compact('ppcb'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ppcb = Ppcb::findOrFail($id);

        $request->validate([
            'ma' => 'required|unique:ppcb,ma,' . $id,
            'ten_ppcb' => 'required',
        ]);

        $ppcb->update($request->all());

        return redirect()->route('ppcb.index')->with('success', 'Cập nhật thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ppcb = Ppcb::findOrFail($id);
        $ppcb->delete();

        return redirect()->route('ppcb.index')->with('success', 'Xóa thành công!');
    }
}