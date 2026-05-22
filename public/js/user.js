$(document).ready(function() {
    // Load data user saat halaman dimuat
    loadUserData();

    // Toggle password visibility
    $(document).on('click', '.toggle-password', function() {
        var button = $(this);
        var target = $(button.data('target'));
        var icon = button.find('i');
        
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Function to load user data
    function loadUserData() {
        $.ajax({
            url: '/user/data',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#user-body').html('<tr><td colspan="8" class="text-center">Memuat data...</td></tr>');
            },
            success: function(response) {
                if (response.data.length > 0) {
                    let tableHtml = '';
                    
                    $.each(response.data, function(index, user) {
                        tableHtml += `
                            <tr id="row-${user.user_id}">
                                <td>${index + 1}</td>
                                <td>${user.username}</td>
                                <td>${user.nama_lengkap}</td>
                                <td>${user.email}</td>
                                <td>${user.telp}</td>
                                <td><span class="badge badge-info">${user.role_nama}</span></td>
                                <td>${user.jenis_kelamin}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${user.user_id}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${user.user_id}" data-name="${user.nama_lengkap}" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#user-body').html(tableHtml);
                } else {
                    $('#user-body').html('<tr><td colspan="8" class="text-center">Belum ada data user</td></tr>');
                }
            },
            error: function(xhr) {
                $('#user-body').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data user');
            }
        });
    }

    // Button tambah user
    $('#btnTambahUser').click(function() {
        resetForm('tambah');
        $('#modalTambahUser').modal('show');
    });

    // Submit form tambah user
    $('#formTambahUser').submit(function(e) {
        e.preventDefault();
        clearErrors('tambah');
        
        $.ajax({
            url: '/user',
            type: 'POST',
            data: $(this).serialize(),
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                $('#modalTambahUser').modal('hide');
                loadUserData();
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors, 'tambah');
                } else {
                    showAlert('danger', xhr.responseJSON.message || 'Terjadi kesalahan');
                }
            }
        });
    });

    // Button edit user
    $(document).on('click', '.btn-edit', function() {
        var userId = $(this).data('id');
        
        resetForm('edit');
        clearErrors('edit');
        
        $.ajax({
            url: '/user/' + userId + '/edit',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                var user = response.data;
                
                $('#edit_user_id').val(user.user_id);
                $('#edit_role_id').val(user.role_id);
                $('#edit_username').val(user.username);
                $('#edit_firstname').val(user.firstname);
                $('#edit_lastname').val(user.lastname);
                $('#edit_email').val(user.email);
                $('#edit_telp').val(user.telp);
                $('#edit_alamat').val(user.alamat);
                $('#edit_jenis_kelamin').val(user.jenis_kelamin);
                $('#edit_tempat_lahir').val(user.tempat_lahir);
                $('#edit_tanggal_lahir').val(user.tanggal_lahir);
                
                $('#modalEditUser').modal('show');
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON.message || 'Gagal mengambil data user');
            }
        });
    });

    // Submit form edit user
    $('#formEditUser').submit(function(e) {
        e.preventDefault();
        clearErrors('edit');
        
        var userId = $('#edit_user_id').val();
        
        $.ajax({
            url: '/user/' + userId,
            type: 'PUT',
            data: $(this).serialize(),
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                $('#modalEditUser').modal('hide');
                loadUserData();
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors, 'edit');
                } else {
                    showAlert('danger', xhr.responseJSON.message || 'Terjadi kesalahan');
                }
            }
        });
    });

    // Button delete user
    $(document).on('click', '.btn-delete', function() {
        var userId = $(this).data('id');
        var userName = $(this).data('name');
        
        AlertHelper.fire({
            title: 'Konfirmasi Hapus',
            html: `Apakah Anda yakin ingin menghapus user:<br><strong>${userName}</strong>?<br><small class="text-danger">Data user akan dihapus secara permanen.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
            cancelButtonText: '<i class="fas fa-times"></i> Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                AlertHelper.fire({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Process delete
                $.ajax({
                    url: '/user/' + userId,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    cache: false,
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    },
                    success: function(response) {
                        AlertHelper.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        loadUserData();
                    },
                    error: function(xhr) {
                        AlertHelper.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON.message || 'Terjadi kesalahan saat menghapus user',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Helper Functions
    function resetForm(mode) {
        if (mode === 'tambah') {
            $('#formTambahUser')[0].reset();
        } else {
            $('#formEditUser')[0].reset();
        }
        clearErrors(mode);
    }

    function clearErrors(mode) {
        var prefix = mode === 'edit' ? 'edit_' : '';
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showValidationErrors(errors, mode) {
        var prefix = mode === 'edit' ? 'edit_' : '';
        
        $.each(errors, function(field, messages) {
            var inputId = '#' + prefix + field;
            var errorId = '#error-' + prefix + field;
            
            $(inputId).addClass('is-invalid');
            $(errorId).text(messages[0]);
        });
    }

    function showAlert(type, message) {
        var alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('#alert-container').html(alertHtml);
        
        // Auto close after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});
