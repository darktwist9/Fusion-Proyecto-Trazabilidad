@if($mostrarUsuario ?? false)
<div class="role-panel-admin-vista mt-2">
    @if($vistaTodosUsuarios ?? false)
        <span class="badge badge-info font-weight-normal px-2 py-1">
            <i class="fas fa-users mr-1"></i> Vista global — todos los usuarios del rol
        </span>
    @elseif($usuarioFiltrado ?? null)
        <span class="badge badge-secondary font-weight-normal px-2 py-1">
            <i class="fas fa-user mr-1"></i> Viendo: {{ $usuarioFiltrado->nombreCompleto() }}
        </span>
    @endif
</div>
@endif
