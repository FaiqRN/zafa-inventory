$(document).ready(function() {
    loadRoles();

    $('#btnTambahRole').click(function() {
        resetForm('#formTambahRole');
        $('#modalTambahRole').modal('show');
    });

    $('#formTambahRole').submit(function(e) {
        e.preventDefault();
        
        const selectedPermissions = $('input[name="permissions[]"]:checked').length;
        if (selectedPermissions === 0) {
            AlertHelper.error('Error', 'Minimal pilih 1 permission');
            return;
        }

        const formData = $(this).serialize();
        const btn = $('#btnSimpanRole');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '/role',
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#modalTambahRole').modal('hide');
                AlertHelper.success('Berhasil', response.message);
                loadRoles();
            },
            error: function(xhr) {
                handleError(xhr, 'formTambahRole');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });

    $(document).on('click', '.btn-edit', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        
        const isAdmin = ['admin', 'superadmin', 'administrator'].includes(roleName.toLowerCase());
        
        if (isAdmin) {
            AlertHelper.error('Error', 'Role Admin tidak dapat diubah. Admin harus memiliki akses penuh ke semua fitur.');
            return;
        }
        
        $.ajax({
            url: `/role/${roleId}/edit`,
            type: 'GET',
            success: function(response) {
                const role = response.data;
                
                $('#edit_role_id').val(role.id);
                $('#edit_name').val(role.name);
                
                $('.edit-permission-checkbox').prop('checked', false);
                
                role.permissions.forEach(function(perm) {
                    $(`#edit_perm_${perm}`).prop('checked', true);
                });
                
                updateModuleCheckboxes('.edit-module-checkbox', '.edit-permission-checkbox');
                
                $('#modalEditRole').modal('show');
            },
            error: function(xhr) {
                AlertHelper.error('Error', 'Gagal memuat data role');
            }
        });
    });

    $('#formEditRole').submit(function(e) {
        e.preventDefault();
        
        const selectedPermissions = $('.edit-permission-checkbox:checked').length;
        if (selectedPermissions === 0) {
            AlertHelper.error('Error', 'Minimal pilih 1 permission');
            return;
        }

        const roleId = $('#edit_role_id').val();
        const formData = $(this).serialize();
        const btn = $('#btnUpdateRole');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: `/role/${roleId}`,
            type: 'PUT',
            data: formData,
            success: function(response) {
                $('#modalEditRole').modal('hide');
                AlertHelper.success('Berhasil', response.message);
                loadRoles();
            },
            error: function(xhr) {
                handleError(xhr, 'formEditRole');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Perubahan');
            }
        });
    });

    $(document).on('click', '.btn-delete', function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        const usersCount = $(this).data('users');
        
        let message = `Apakah Anda yakin ingin menghapus role "${roleName}"?`;
        if (usersCount > 0) {
            message += ` ${usersCount} user dengan role ini akan kehilangan akses dan hanya dapat mengakses dashboard.`;
        }
        
        AlertHelper.confirmDelete(`Hapus Role "${roleName}"?`, message).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/role/${roleId}`,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        AlertHelper.success('Berhasil!', response.message);
                        loadRoles();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        AlertHelper.error('Gagal!', response.message || 'Gagal menghapus role');
                    }
                });
            }
        });
    });

    $('#btnSelectAll').click(function() {
        $('.permission-checkbox').prop('checked', true);
        $('.module-checkbox').prop('checked', true);
    });

    $('#btnDeselectAll').click(function() {
        $('.permission-checkbox').prop('checked', false);
        $('.module-checkbox').prop('checked', false);
    });

    $('#btnEditSelectAll').click(function() {
        $('.edit-permission-checkbox').prop('checked', true);
        $('.edit-module-checkbox').prop('checked', true);
    });

    $('#btnEditDeselectAll').click(function() {
        $('.edit-permission-checkbox').prop('checked', false);
        $('.edit-module-checkbox').prop('checked', false);
    });

    $(document).on('change', '.module-checkbox', function() {
        const module = $(this).data('module');
        const isChecked = $(this).prop('checked');
        $(`.permission-checkbox[data-module="${module}"]`).prop('checked', isChecked);
    });

    $(document).on('change', '.edit-module-checkbox', function() {
        const module = $(this).data('module');
        const isChecked = $(this).prop('checked');
        $(`.edit-permission-checkbox[data-module="${module}"]`).prop('checked', isChecked);
    });

    $(document).on('change', '.permission-checkbox', function() {
        updateModuleCheckboxes('.module-checkbox', '.permission-checkbox');
    });

    $(document).on('change', '.edit-permission-checkbox', function() {
        updateModuleCheckboxes('.edit-module-checkbox', '.edit-permission-checkbox');
    });
});

function loadRoles() {
    $.ajax({
        url: '/role/data',
        type: 'GET',
        success: function(response) {
            const tbody = $('#role-body');
            tbody.empty();

            if (response.data.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data</td>
                    </tr>
                `);
                return;
            }

            response.data.forEach(function(role, index) {
                const isAdmin = ['admin', 'superadmin', 'administrator'].includes(role.name.toLowerCase());
                
                const editBtn = isAdmin 
                    ? '<button class="btn btn-sm btn-secondary" disabled title="Role Admin tidak dapat diubah"><i class="fas fa-lock"></i> Terkunci</button>'
                    : `<button class="btn btn-sm btn-info btn-edit" data-id="${role.id}" data-name="${role.name}"><i class="fas fa-edit"></i> Edit</button>`;
                    
                const deleteBtn = isAdmin 
                    ? '<button class="btn btn-sm btn-secondary" disabled title="Role Admin tidak dapat dihapus"><i class="fas fa-ban"></i></button>'
                    : `<button class="btn btn-sm btn-danger btn-delete" data-id="${role.id}" data-name="${role.name}" data-users="${role.users_count}"><i class="fas fa-trash"></i> Hapus</button>`;

                const badge = isAdmin ? '<span class="badge badge-success ml-2">Full Access</span>' : '';

                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${role.name}</strong>${badge}</td>
                        <td>${role.users_count} user</td>
                        <td>${role.permissions_count} permissions</td>
                        <td>${role.created_at}</td>
                        <td>
                            ${editBtn}
                            ${deleteBtn}
                        </td>
                    </tr>
                `);
            });
        },
        error: function() {
            AlertHelper.error('Error', 'Gagal memuat data role');
        }
    });
}

function updateModuleCheckboxes(moduleClass, permClass) {
    $(moduleClass).each(function() {
        const module = $(this).data('module');
        const totalPerms = $(`${permClass}[data-module="${module}"]`).length;
        const checkedPerms = $(`${permClass}[data-module="${module}"]:checked`).length;
        
        $(this).prop('checked', totalPerms === checkedPerms);
    });
}

function resetForm(formId) {
    $(formId)[0].reset();
    $(formId).find('.is-invalid').removeClass('is-invalid');
    $(formId).find('.invalid-feedback').text('');
    $('.permission-checkbox').prop('checked', false);
    $('.module-checkbox').prop('checked', false);
}

function handleError(xhr, formId) {
    const response = xhr.responseJSON;
    
    if (response.errors) {
        Object.keys(response.errors).forEach(function(key) {
            const errorElement = $(`#error-${key}`);
            const inputElement = $(`#${key}`);
            
            errorElement.text(response.errors[key][0]);
            inputElement.addClass('is-invalid');
        });
    }
    
    AlertHelper.error('Error', response.message || 'Terjadi kesalahan');
}


