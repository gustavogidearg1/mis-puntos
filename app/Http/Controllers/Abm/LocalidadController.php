<?php

namespace App\Http\Controllers\Abm;

use App\Http\Controllers\Controller;
use App\Models\Localidad;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Http\Request;

class LocalidadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin_sitio']);
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $localidades = Localidad::query()
            ->with(['provincia.pais'])
            ->when($q, function ($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('cp', 'like', "%{$q}%")
                   ->orWhereHas('provincia', fn($p) => $p->where('nombre', 'like', "%{$q}%"))
                   ->orWhereHas('provincia.pais', fn($pa) => $pa->where('nombre', 'like', "%{$q}%"));
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('abm.localidades.index', compact('localidades', 'q'));
    }

    public function create()
    {
    $provincias = Provincia::with('pais')->orderBy('nombre')->get();
    return view('abm.localidades.create', compact('provincias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provincia_id' => ['required', 'integer', 'exists:provincias,id'],
            'nombre'       => ['required', 'string', 'max:120'],
            'cp'           => ['nullable', 'string', 'max:12'],
        ]);

        Localidad::create($data);

        return redirect()
            ->route('abm.localidades.index')
            ->with('success', 'Localidad created successfully.');
    }

    public function show(Localidad $localidad)
    {
        $localidad->load(['provincia.pais']);
        return view('abm.localidades.show', compact('localidad'));
    }

    public function edit(Localidad $localidad)
    {
    $provincias = Provincia::with('pais')->orderBy('nombre')->get();
    return view('abm.localidades.edit', compact('localidad','provincias'));
    }

    public function update(Request $request, Localidad $localidad)
    {
        $data = $request->validate([
            'provincia_id' => ['required', 'integer', 'exists:provincias,id'],
            'nombre'       => ['required', 'string', 'max:120'],
            'cp'           => ['nullable', 'string', 'max:12'],
        ]);

        $localidad->update($data);

        return redirect()
            ->route('abm.localidades.index')
            ->with('success', 'Localidad updated successfully.');
    }

    public function destroy(Localidad $localidad)
    {
        $localidad->delete();

        return redirect()
            ->route('abm.localidades.index')
            ->with('success', 'Localidad deleted successfully.');
    }
}
