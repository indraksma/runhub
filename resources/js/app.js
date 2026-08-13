import 'trix';
import 'trix/dist/trix.css';

document.addEventListener('trix-attachment-add', async (event) => {
    const attachment = event.attachment;

    if (!attachment.file || !event.target.matches('[data-event-description-editor]')) {
        return;
    }

    const editor = event.target;
    const data = new FormData();
    data.append('image', attachment.file);
    attachment.setUploadProgress(10);

    try {
        const response = await fetch(editor.dataset.uploadUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: data,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(payload.message || 'Gambar gagal diunggah.');
        }

        const payload = await response.json();
        attachment.setAttributes({ url: payload.url, href: payload.url });
        attachment.setUploadProgress(100);
    } catch (error) {
        attachment.remove();
        window.dispatchEvent(new CustomEvent('app:toast', {
            detail: { type: 'error', message: error.message },
        }));
    }
});
