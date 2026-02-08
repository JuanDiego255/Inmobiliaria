<div class="share-links-section">
    <div class="mb-3">
        <label class="form-label">{{ trans('plugins/real-estate::board.copy_link') }}</label>
        <div class="input-group">
            <input type="text" class="form-control" id="board-public-url" value="{{ $board->public_url }}" readonly>
            <button class="btn btn-outline-secondary" type="button" id="copy-board-url" title="{{ trans('plugins/real-estate::board.copy_link') }}">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $board->whatsapp_share_url }}" target="_blank" class="btn btn-success btn-sm">
            <i class="fab fa-whatsapp"></i> {{ trans('plugins/real-estate::board.share_via_whatsapp') }}
        </a>

        <a href="{{ $board->email_share_url }}" class="btn btn-primary btn-sm">
            <i class="fas fa-envelope"></i> {{ trans('plugins/real-estate::board.share_via_email') }}
        </a>

        <a href="{{ $board->public_url }}" target="_blank" class="btn btn-info btn-sm">
            <i class="fas fa-external-link-alt"></i> {{ trans('plugins/real-estate::board.public_view') }}
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var copyBtn = document.getElementById('copy-board-url');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var input = document.getElementById('board-public-url');
                input.select();
                input.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(input.value);
                this.innerHTML = '<i class="fas fa-check"></i>';
                var btn = this;
                setTimeout(function () {
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        }
    });
</script>
