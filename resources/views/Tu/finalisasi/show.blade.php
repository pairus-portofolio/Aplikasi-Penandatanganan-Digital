@extends('layouts.app')

@section('title', 'Detail Surat')

@section('content')

<div style="width: 100%; display: flex; justify-content: center; margin-top: 40px;">
    <div style="text-align: center;">

        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 5px;">
            Dokumen siap di finalisasi
        </h2>

        <p style="color: #555; margin-bottom: 30px; font-size: 14px;">
            Pratinjau dokumen Anda di sini sebelum menyelesaikan,
        </p>

        <div style="
            width: 350px;
            height: 430px;
            border: 2px dashed #bfbfbf;
            border-radius: 10px; 
            margin: auto;
            background: #fff;
            overflow: hidden;
        ">
            <iframe 
                src="{{ route('tu.finalisasi.preview', $document->id) }}#toolbar=0&navpanes=0&scrollbar=0" 
                width="100%"
                height="100%"
                frameborder="0"
                style="overflow: hidden;"
            >
                Dokumen tidak dapat ditampilkan. Coba unduh.
            </iframe>
        </div>

        {{-- TOMBOL TIDAK LANGSUNG SUBMIT --}}
        <button 
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#modalFinalisasi"
            style="
                width: 350px;
                margin-top: 30px;
                background: #0d99ff;
                color: white;
                padding: 12px 0;
                border: none;
                border-radius: 30px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            ">
            Finalisasi Surat
        </button>

        <p style="margin-top: 12px; color: #555; font-size: 13px;">
            Pastikan semua informasi sudah benar sebelum melakukan finalisasi.
        </p>

        <a href="{{ route('tu.finalisasi.index') }}"
            style="
                display: inline-block;
                margin-top: 20px;
                text-decoration: none;
                color: #555;
                font-size: 14px;
            ">
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
<script src="{{ asset('js/finalisasi.js') }}"></script>
@endpush
