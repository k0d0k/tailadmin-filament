@php
    use App\Support\Acl;

    $user = auth()->user();
    $isSuper = $user?->hasRole(Acl::SUPER_ADMIN_ROLE) ?? false;
    $permission = $permission ?? $resource;
    $canUpdate = $isSuper || ($user?->checkPermissionTo(Acl::name('update', $permission)) ?? false);
    $canDelete = $isSuper || ($user?->checkPermissionTo(Acl::name('delete', $permission)) ?? false);
@endphp

<div class="flex items-center justify-end gap-1.5">
    @if ($canUpdate)
        <a href="{{ route('panel.edit', [$resource, $slug]) }}" class="ta-icon-btn" title="Ubah">
            <x-panel-icon name="pencil" class="h-4 w-4" />
        </a>
    @endif

    @if ($canDelete)
        <button type="button"
                data-delete-url="{{ route('panel.destroy', [$resource, $slug]) }}"
                data-confirm="Hapus data ini? Tindakan ini tidak dapat dibatalkan."
                class="ta-icon-btn hover:!border-error-500/40 hover:!text-error-500"
                title="Hapus">
            <x-panel-icon name="trash" class="h-4 w-4" />
        </button>
    @endif
</div>
