import 'trix';
import 'trix/dist/trix.css';

const imageResizeState = new WeakMap();

const selectedImageAttachment = (editor) => {
    const attachments = editor.editor?.getSelectedDocument()?.getAttachments() || [];

    return attachments.find((attachment) => /^image(?:\/|$)/.test(attachment.getAttribute('contentType') || '')) || null;
};

const findAttachmentImage = (editor, attachment) => {
    const url = attachment?.getAttribute('url');

    return [...editor.querySelectorAll('figure[data-trix-attachment] img')]
        .find((image) => image.getAttribute('src') === url || image.currentSrc === url) || null;
};

const updateImageResizeControls = (editor) => {
    const controls = editor.parentElement?.querySelector('[data-image-resize-controls]');

    if (!controls) {
        return;
    }

    const attachment = selectedImageAttachment(editor);
    const buttons = [...controls.querySelectorAll('[data-image-size]')];
    const status = controls.querySelector('[data-image-resize-status]');

    if (!attachment) {
        imageResizeState.delete(editor);
        buttons.forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-pressed', 'false');
        });
        status.textContent = 'Pilih gambar di editor untuk mengatur ukurannya.';
        return;
    }

    const image = findAttachmentImage(editor, attachment);
    const width = Number(attachment.getAttribute('width')) || image?.width || 0;
    const height = Number(attachment.getAttribute('height')) || image?.height || 0;
    const originalWidth = image?.naturalWidth || width;
    const originalHeight = image?.naturalHeight || height;

    imageResizeState.set(editor, { attachment, originalWidth, originalHeight });
    buttons.forEach((button) => {
        button.disabled = !originalWidth || !originalHeight;
        const targetWidth = button.dataset.imageSize === 'small'
            ? Math.min(320, originalWidth)
            : button.dataset.imageSize === 'medium'
                ? Math.min(560, originalWidth)
                : originalWidth;
        button.setAttribute('aria-pressed', String(Math.abs(width - targetWidth) <= 1));
    });
    status.textContent = width ? `Gambar terpilih: ${width} px.` : 'Gambar terpilih.';
};

document.addEventListener('trix-initialize', (event) => {
    const editor = event.target;

    if (!editor.matches('[data-event-description-editor]')) {
        return;
    }

    const controls = editor.parentElement?.querySelector('[data-image-resize-controls]');

    controls?.addEventListener('click', (clickEvent) => {
        const button = clickEvent.target.closest('[data-image-size]');
        const state = imageResizeState.get(editor);

        if (!button || button.disabled || !state) {
            return;
        }

        const requestedWidth = button.dataset.imageSize === 'small'
            ? 320
            : button.dataset.imageSize === 'medium'
                ? 560
                : state.originalWidth;
        const width = Math.min(requestedWidth, state.originalWidth);
        const height = Math.round(width * state.originalHeight / state.originalWidth);

        state.attachment.setAttributes({ width, height });
        requestAnimationFrame(() => updateImageResizeControls(editor));
    });

    editor.addEventListener('click', () => requestAnimationFrame(() => updateImageResizeControls(editor)));
    updateImageResizeControls(editor);
});

document.addEventListener('trix-selection-change', (event) => {
    if (event.target.matches('[data-event-description-editor]')) {
        updateImageResizeControls(event.target);
    }
});

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
        requestAnimationFrame(() => updateImageResizeControls(editor));
    } catch (error) {
        attachment.remove();
        window.dispatchEvent(new CustomEvent('app:toast', {
            detail: { type: 'error', message: error.message },
        }));
    }
});
