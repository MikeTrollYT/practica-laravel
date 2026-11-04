<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $project->title }}
            </h2>
            @can('update', $project)
                <div class="flex gap-2">
                    <a href="{{ route('projects.edit', $project) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                        Editar
                    </a>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('¿Estás seguro de eliminar este proyecto?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Eliminar
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Información del Proyecto -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Detalles del Proyecto</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Propietario</p>
                            <p class="mt-1">{{ $project->owner->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Estado</p>
                            <p class="mt-1">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($project->status === 'completed') bg-green-100 text-green-800
                                    @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($project->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha de Vencimiento</p>
                            <p class="mt-1">{{ $project->due_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Creado</p>
                            <p class="mt-1">{{ $project->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Descripción</p>
                        <p class="mt-1 text-gray-700">{{ $project->description }}</p>
                    </div>
                </div>
            </div>

            <!-- Gestión de Participantes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Participantes del Proyecto</h3>

                    @if($project->participants->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horas Trabajadas</th>
                                        @can('update', $project)
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($project->participants as $participant)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $participant->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $participant->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                                    {{ $participant->pivot->role }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @can('update', $project)
                                                    <form method="POST" action="{{ route('projects.participants.hours', [$project, $participant]) }}" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="number" name="contribution_hours" value="{{ $participant->pivot->contribution_hours }}"
                                                            min="0" class="w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">Actualizar</button>
                                                    </form>
                                                @else
                                                    {{ $participant->pivot->contribution_hours }} horas
                                                @endcan
                                            </td>
                                            @can('update', $project)
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <form method="POST" action="{{ route('projects.participants.remove', [$project, $participant]) }}"
                                                        onsubmit="return confirm('¿Estás seguro de eliminar este participante?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                                                    </form>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No hay participantes asignados a este proyecto.</p>
                    @endif

                    @can('update', $project)
                        @if($availableUsers->count() > 0)
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-md font-semibold mb-3">Añadir Participante</h4>
                                <form method="POST" action="{{ route('projects.participants.add', $project) }}" class="flex flex-col sm:flex-row gap-3">
                                    @csrf
                                    <div class="flex-1">
                                        <select name="user_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Seleccionar usuario...</option>
                                            @foreach($availableUsers as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" name="role" placeholder="Rol (ej: Desarrollador, Diseñador)" required
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded whitespace-nowrap">
                                        Añadir
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-gray-500 text-sm mt-4 text-center">No hay usuarios disponibles para añadir al proyecto.</p>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- Botón de regreso -->
            <div class="flex justify-start">
                <a href="{{ route('projects.index') }}" class="text-blue-600 hover:text-blue-800">
                    ← Volver a la lista de proyectos
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
