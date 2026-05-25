import EditorJS from '@editorjs/editorjs';
import button from './tools/Button';
import code from '@editorjs/code';
import delimiter from '@editorjs/delimiter';
import editorjs from '@editorjs/editorjs';
import header from '@editorjs/header';
import image from '@editorjs/image';
import inlineCode from '@editorjs/inline-code';
import list from '@editorjs/list';
import quote from '@editorjs/quote';
import raw from '@editorjs/raw';
import table from '@editorjs/table';
import underline from '@editorjs/underline';

function upload(data) {
    return fetch(window.uploadUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: {
            'X-CSRF-Token': window.csrfToken,
        },
    }).then((response) => response.json());
}

window.initializeEditorJs = function (el, data, options, onChange) {
    return new EditorJS({
        holder: el,

        data,

        autofocus: true,
        placeholder: window.__('Write something awesome!'),
        logLevel: 'ERROR',
        minHeight: 5,
        i18n: {
            direction: options.direction,
        },

        tools: {
            button: button,
            code,
            delimiter,
            editorjs,
            header: {
                class: header,
                config: {
                    levels: [1, 2, 3, 4],
                },
            },
            image: {
                class: image,
                config: {
                    uploader: {
                        uploadByFile(file) {
                            const data = new FormData();
                            data.append('file', file);

                            return upload(data);
                        },

                        uploadByUrl(url) {
                            const data = new FormData();
                            data.append('url', url);

                            return upload(data);
                        },
                    },
                },
            },
            inlineCode: {
                class: inlineCode,
                shortcut: 'CMD+SHIFT+M',
            },
            list,
            quote,
            raw,
            table,
            underline,
        },

        onChange: async (api) => {
            const data = await api.saver.save();

            onChange(data);
        },
    });
};
