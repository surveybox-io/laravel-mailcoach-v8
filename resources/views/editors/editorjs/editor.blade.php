<div class="form-grid">
    <script>
        window.debounce = function(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }
    </script>

    <style>
        .btn.btn--default {
            color: #fff !important;
        }

        .btn.btn--default:hover {
            color: #fff;
            background: #0a59da;
        }

        .codex-editor.codex-editor--toolbox-opened {
            z-index: 100;
        }
    </style>
    <script>
        window.uploadUrl = '{{ action(\Spatie\Mailcoach\Http\Api\Controllers\UploadsController::class) }}';
        window.csrfToken = '{{ csrf_token() }}';
    </script>

    @if ($model->hasTemplates())
        <x-mailcoach::template-chooser :clearable="false" />
    @endif

    @foreach($template?->fields() ?? [['name' => 'html', 'type' => 'editor']] as $index => $field)
        <x-mailcoach::editor-fields :name="$field['name']" :type="$field['type']" :label="$field['name'] === 'html' ? 'Content' : null">
            <x-slot name="editor">
                <div class="relative prose w-full max-w-[800px] border rounded-lg py-6">
                    <div
                        wire:ignore
                        wire:key="{{ $field['name'] . '-' . $index }}"
                        x-data="{
                            html: @entangle('templateFieldValues.' . $field['name'] . '.html').live,
                            json: @entangle('templateFieldValues.' . $field['name'] . '.json'),
                        }"
                        x-init="
                            initializeEditorJs($refs.editor, json, {
                                direction: '{{ config('mailcoach.editorjs.options.text_direction', 'ltr') }}',
                            }, window.debounce((data) => {
                                json = data;
                                fetch('{{ action(\Spatie\Mailcoach\Http\Api\Controllers\Editors\EditorJs\RenderEditorController::class) }}', {
                                    method: 'POST',
                                    body: JSON.stringify(data),
                                    credentials: 'same-origin',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-Token': '{{ csrf_token() }}',
                                    }
                                }).then(response => response.json())
                                  .then((data) => {
                                      html = data.html;
                                  });
                            }))
                        "
                        class=""
                    >
                        <div x-ref="editor"></div>
                    </div>
                </div>
            </x-slot>
        </x-mailcoach::editor-fields>
    @endforeach
</div>
