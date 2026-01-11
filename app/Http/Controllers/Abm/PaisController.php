<?php

namespace App\Http\Controllers\Abm;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use Illuminate\Http\Request;

class PaisController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin_sitio']);
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $paises = Pais::query()
            ->when($q, fn ($qq) =>
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('iso2', 'like', "%{$q}%")
                   ->orWhere('iso3', 'like', "%{$q}%")
            )
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('abm.paises.index', compact('paises', 'q'));
    }

    public function create()
    {
        return view('abm.paises.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'iso2'   => ['nullable', 'string', 'size:2'],
            'iso3'   => ['nullable', 'string', 'size:3'],
        ]);

        Pais::create($data);

        return redirect()
            ->route('abm.paises.index')
            ->with('success', 'País creado correctamente.');
    }

    public function show(Pais $pais)
    {
        return view('abm.paises.show', compact('pais'));
    }

    public function edit(Pais $pais)
    {
        return view('abm.paises.edit', compact('pais'));
    }

    public function update(Request $request, Pais $pais)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'iso2'   => ['nullable', 'string', 'size:2'],
            'iso3'   => ['nullable', 'string', 'size:3'],
        ]);

        $pais->update($data);

        return redirect()
            ->route('abm.paises.index')
            ->with('success', 'País actualizado correctamente.');
    }

    public function destroy(Pais $pais)
    {
        $pais->delete();

        return redirect()
            ->route('abm.paises.index')
            ->with('success', 'País eliminado correctamente.');
    }
}
