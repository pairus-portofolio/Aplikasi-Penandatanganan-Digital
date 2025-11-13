<div class="cards">
  <!-- {{-- TU --}} -->
  @if (Auth::check() && Auth::user()->role->nama_role === 'TU')
    <div class="card">
      <div class="cap"><i class="fa-regular fa-envelope-open"></i> Surat Keluar</div>
      <div class="card-body">
        <p class="num">{{ $suratKeluarCount ?? 0 }}</p>
        <div class="meta">Jumlah surat keluar</div>
      </div>
    </div>
  @endif

  <!-- {{-- Kaprodi D3 dan D4 --}} -->
  @if (Auth::check() && in_array(Auth::user()->role->nama_role, ['Kaprodi D3', 'Kaprodi D4']))
    <div class="card">
      <div class="cap"><i class="fa-solid fa-file-signature"></i> Surat Perlu Direview</div>
      <div class="card-body">
        <p class="num">{{ $suratPerluReview ?? 0 }}</p>
        <div class="meta">Surat yang menunggu review</div>
      </div>
    </div>

    <div class="card">
      <div class="cap"><i class="fa-solid fa-pen-nib"></i> Surat Perlu Paraf</div>
      <div class="card-body">
        <p class="num">{{ $suratPerluParaf ?? 0 }}</p>
        <div class="meta">Surat yang menunggu paraf</div>
      </div>
    </div>
  @endif
<!-- 
  {{-- Kajur dan Sekjur --}} -->
  @if (Auth::check() && in_array(Auth::user()->role->nama_role, ['Kajur', 'Sekjur']))
    <div class="card">
      <div class="cap"><i class="fa-solid fa-stamp"></i> Surat Perlu Tanda Tangan</div>
      <div class="card-body">
        <p class="num">{{ $suratPerluTtd ?? 0 }}</p>
        <div class="meta">Surat yang menunggu tanda tangan</div>
      </div>
    </div>
  @endif
</div>
