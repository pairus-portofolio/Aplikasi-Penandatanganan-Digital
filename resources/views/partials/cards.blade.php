<div class="cards">

  <!-- Kartu khusus role TU -->
  @if (Auth::check() && Auth::user()->role->nama_role === 'TU')
    <div class="card">
      <div class="cap">
        <i class="fa-regular fa-envelope-open"></i> Surat Keluar
      </div>

      <div class="card-body">
        <!-- Menampilkan jumlah surat keluar -->
        <p class="num">{{ $suratKeluarCount ?? 0 }}</p>
        <div class="meta">Jumlah surat keluar</div>
      </div>
    </div>
  @endif


  <!-- Kartu khusus role Kaprodi D3 dan Kaprodi D4 -->
  @if (Auth::check() && in_array(Auth::user()->role->nama_role, ['Kaprodi D3', 'Kaprodi D4']))

    <!-- Kartu surat yang perlu direview -->
    <div class="card">
      <div class="cap">
        <i class="fa-solid fa-file-signature"></i> Surat Perlu Direview
      </div>

      <div class="card-body">
        <!-- Jumlah surat yang menunggu review -->
        <p class="num">{{ $suratPerluReview ?? 0 }}</p>
        <div class="meta">Surat yang menunggu review</div>
      </div>
    </div>

    <!-- Kartu surat yang perlu paraf -->
    <div class="card">
      <div class="cap">
        <i class="fa-solid fa-pen-nib"></i> Surat Perlu Paraf
      </div>

      <div class="card-body">
        <!-- Jumlah surat yang menunggu paraf -->
        <p class="num">{{ $suratPerluParaf ?? 0 }}</p>
        <div class="meta">Surat yang menunggu paraf</div>
      </div>
    </div>

  @endif


  <!-- Kartu khusus role Kajur dan Sekjur -->
  @if (Auth::check() && in_array(Auth::user()->role->nama_role, ['Kajur', 'Sekjur']))

    <!-- Kartu surat yang perlu tanda tangan -->
    <div class="card">
      <div class="cap">
        <i class="fa-solid fa-stamp"></i> Surat Perlu Tanda Tangan
      </div>

      <div class="card-body">
        <!-- Jumlah surat yang menunggu tanda tangan -->
        <p class="num">{{ $suratPerluTtd ?? 0 }}</p>
        <div class="meta">Surat yang menunggu tanda tangan</div>
      </div>
    </div>

  @endif

</div>
