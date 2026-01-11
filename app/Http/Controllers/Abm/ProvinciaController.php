<?php

namespace App\Http\Controllers\Abm;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin_sitio']);
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $provincias = Provincia::query()
            ->with('pais')
            ->when($q, function ($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhereHas('pais', fn ($p) =>
                        $p->where('nombre', 'like', "%{$q}%")
                   );
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('abm.provincias.index', compact('provincias', 'q'));
    }

    public function create()
    {
        $paises = Pais::orderBy('nombre')->get();
        return view('abm.provincias.create', compact('paises'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pais_id' => ['required', 'integer', 'exists:paises,id'],
            'nombre'  => ['required', 'string', 'max:100'],
        ]);

        Provincia::create($data);

        return redirect()
            ->route('abm.provincias.index')
            ->with('success', 'Provincia creada correctamente.');
    }

    public function show(Provincia $provincia)
    {
        $provincia->load('pais');
        return view('abm.provincias.show', compact('provincia'));
    }

    public function edit(Provincia $provincia)
    {
        $paises = Pais::orderBy('nombre')->get();
        return view('abm.provincias.edit', compact('provincia', 'paises'));
    }

    public function update(Request $request, Provincia $provincia)
    {
        $data = $request->validate([
            'pais_id' => ['required', 'integer', 'exists:paises,id'],
            'nombre'  => ['required', 'string', 'max:100'],
        ]);

        $provincia->update($data);

        return redirect()
            ->route('abm.provincias.index')
            ->with('success', 'Provincia actualizada correctamente.');
    }

    public function destroy(Provincia $provincia)
    {
        $provincia->delete();

        return redirect()
            ->route('abm.provincias.index')
            ->with('success', 'Provincia eliminada correctamente.');
    }
}
