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

        {{-- BUTTON FINALISASI dengan ModalManager --}}
        <button
            type="button"
            data-modal="finalisasi-confirm"
            data-finalize-url="{{ route('tu.finalisasi.store', $document->id) }}"
            data-download-url="{{ route('tu.finalisasi.download', $document->id) }}"
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

{{-- Modal handled by ModalManager (modal-manager.js) --}}

@push('scripts')

<script src="{{ asset('js/tu/finalisasi.js') }}"></script>
@endpush