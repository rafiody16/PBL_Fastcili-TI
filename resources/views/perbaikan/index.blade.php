@extends('layouts.app', [
    'class' => '',
    'elementActive' => 'perbaikan_teknisi',
])

@section('content')
    <div class="content">
        <h3>Daftar Perbaikan</h3>
        <div class="card p-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-row-bordered" id="table_perbaikan">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                @if (auth()->user()->id_level != 1)
                                    <th scope="col">Foto Kerusakan</th>
                                @endif
                                <th scope="col">Deskripsi</th>
                                <th scope="col">Teknisi</th>
                                <th scope="col">Tenggat</th>
                                <th scope="col">Status</th>
                                <th scope="col-">Catatan Teknisi</th>
                                <th scope="col">Dokumentasi Perbaikan</th>
                                <th scope="col">Catatan Sarpras</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laporan_kerusakan as $index => $laporan)
                                <tr>
                                    <th scope="row">{{ $index + 1 }}</th>
                                    @if (auth()->user()->id_level != 1)
                                        {{-- Foto Kerusakan --}}
                                        <td>
                                            <img src="{{ asset('storage/uploads/laporan_kerusakan/' . $laporan->laporan->foto_kerusakan) }}"
                                                alt="Foto Kerusakan" height="65"
                                                onerror="this.onerror=null;this.src='{{ asset('images/fasilitas-rusak.jpeg') }}';">
                                        </td>
                                    @endif
                                    {{-- Deskripsi --}}
                                    <td>{{ $laporan->laporan->deskripsi ?? '-' }}</td>

                                    {{-- Nama Teknisi --}}
                                    <td>{{ $laporan->laporan->penugasan->user->nama ?? '-' }}</td>
                                    <td> {{ $laporan->tenggat
                                        ? \Carbon\Carbon::parse($laporan->tenggat)->locale('id')->translatedFormat('l, d F Y')
                                        : '-' }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-pill {{ $laporan->status_perbaikan == 'Sedang dikerjakan' ? 'badge-warning' : 'badge-success' }}">
                                            {{ $laporan->status_perbaikan }}
                                        </span>
                                    </td>

                                    {{-- Catatan Teknisi --}}
                                    <td>{{ Str::limit($laporan->catatan_teknisi, 20) ?? '-' }}</td>

                                    {{-- Dokumentasi --}}
                                    <td>
                                        @if ($laporan->dokumentasi)
                                            <img src="{{ asset('storage/uploads/dokumentasi/' . $laporan->dokumentasi) }}"
                                                alt="Foto Kerusakan" height="65">
                                        @else
                                            <span class="text-danger">(Belum ada dokumentasi)</span>
                                        @endif
                                    </td>

                                    {{-- Catatan Teknisi --}}
                                    <td>{{ Str::limit($laporan->komentar_sarpras, 20) ?? '-' }}</td>

                                    {{-- Tombol Aksi --}}
                                    <td>
                                        <div class="d-flex">
                                            @php
                                                $isEditable = $laporan->status_perbaikan != 'Selesai';
                                                $isRejected = !is_null($laporan->komentar_sarpras);
                                                $isReported = !is_null($laporan->dokumentasi);

                                                $laporanUrl = url('/perbaikan/edit/' . $laporan->id_penugasan);
                                                $detailUrl = url('/perbaikan/detail/' . $laporan->id_penugasan);
                                            @endphp

                                            @if (auth()->user()->id_level != 1)
                                                @if ($isEditable)
                                                    <button onclick="modalAction('{{ $laporanUrl }}')"
                                                        class="btn btn-sm btn-danger mr-2">
                                                        {{ $isRejected ? 'Edit Laporan' : 'Laporkan' }}
                                                    </button>
                                                @endif
                                            @endif

                                            <button onclick="modalAction('{{ $detailUrl }}')"
                                                class="btn btn-sm btn-info">
                                                Detail
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
        aria-hidden="true"></div>
@endsection

@push('scripts')
    <script>
        function modalAction(url = '') {
            $('#myModal').load(url, function() {
                $('#myModal').modal('show');
            });
        }
        var dataLaporan;

        $(document).on('submit', '.form-delete', function(e) {
            e.preventDefault(); // Cegah submit form langsung
            let form = this;
            let url = $(this).data('url');

            Swal.fire({
                title: 'Apakah Anda yakin ingin menghapus data ini?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: $(this).attr('action'),
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $('#myModal').modal('hide');
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    text: response.messages,
                                });
                                location.reload();
                            } else {
                                alert('Gagal menghapus data.');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.msgField) {
                                let errors = xhr.responseJSON.msgField;
                                $.each(errors, function(field, messages) {
                                    $('#error-' + field).text(messages[0]);
                                });
                            } else {
                                Swal.fire({
                                    icon: "error",
                                    title: "Gagal!",
                                    text: response.messages,
                                });
                            }
                        }
                    });
                }
            });
        });
        const user = {
            id_level: {{ auth()->user()->id_level }} // Data user di-pass dari backend
        };

        $(document).ready(function() {
            // Common configuration options
            var commonConfig = {
                language: {
                    emptyTable: "<i class='fas fa-info-circle'></i> Tidak ada data perbaikan yang tersedia",
                    zeroRecords: "<i class='fas fa-info-circle'></i> Tidak ada data perbaikan seperti keyword yang ingin dicari"
                }
            };

            // Configuration for admin/ (level 1)
            if (user.id_level !== 1) {
                var datalaporan = $('#table_perbaikan').DataTable({
                    ...commonConfig,
                    columnDefs: [{
                        targets: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                        className: 'text-center',
                    }, {
                        targets: [0, 1, 2, 5, 6, 7, 8],
                        orderable: false,
                        searchable: true,
                    }, {
                        targets: [3, 4],
                        searchable: true,
                    }]
                });
            }
            // Configuration for other users
            else {
                var datalaporan = $('#table_perbaikan').DataTable({
                    ...commonConfig,
                    columnDefs: [{
                        targets: [0, 1, 2, 3, 4, 5, 6, 7],
                        className: 'text-center',
                    }, {
                        targets: [0, 1, 4, 5, 6, 7],
                        orderable: false,
                        searchable: true,
                    }, {
                        targets: [2, 3],
                        searchable: true,
                    }]
                });
            }
        });
    </script>
@endpush
