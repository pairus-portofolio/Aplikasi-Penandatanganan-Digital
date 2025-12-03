@extends('layouts.app')

@section('title', 'Detail Surat')

@section('content')

<link rel="stylesheet" href="{{ asset('css/tu/finalisasi.css') }}">

<div class="preview-wrapper">
    <div class="preview-content">

        <h2>Dokumen siap di finalisasi</h2>

        <p class="desc">
            Pratinjau dokumen Anda di sini sebelum menyelesaikan,
        </p>

        <div class="preview-box">
            <iframe
                src="{{ route('tu.finalisasi.preview', $document->id) }}#toolbar=0&navpanes=0&scrollbar=0"
                title="Pratinjau dokumen finalisasi"
            >
                Dokumen tidak dapat ditampilkan. Coba unduh.
            </iframe>
        </div>

        {{-- BUTTON FINALISASI --}}
        <button
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#modalFinalisasi"
            class="btn-final"
        >
            Finalisasi Surat
        </button>

        <p class="warning">
            Pastikan semua informasi sudah benar sebelum melakukan finalisasi.
        </p>

        <a href="{{ route('tu.finalisasi.index') }}" class="back-link">
            Kembali
        </a>

    </div>
</div>

@endsection

{{-- ===================== MODAL ===================== --}}
@section('popup')
<div class="modal fade" id="modalFinalisasi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Finalisasi Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <a href="{{ route('tu.finalisasi.download', $document->id) }}" class="btn btn-success w-100 mb-2">
                    Download
                </a>
            </div>

            <div class="modal-footer">
                <form method="POST" action="{{ route('tu.finalisasi.store', $document->id) }}">
                    @csrf
                    <input type="hidden" name="aksi" value="final">
                    <button type="submit" class="btn btn-danger">
                        Ya, Finalisasi Sekarang
                    </button>
                </form>

                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Batalkan
                </button>
            </div>

        </div>
    </div>
</div>

@include('partials.logout-popup')
@endsection


@push('scripts')
<script src="{{ asset('js/tu/finalisasi.js') }}"></script>
@endpush