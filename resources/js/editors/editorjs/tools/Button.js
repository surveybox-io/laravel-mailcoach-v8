export default class Button {
    /**
     * @returns {{icon: string, title: string}}
     */
    static get toolbox() {
        return {
            title: 'Button',
            icon:
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' +
                '  <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" />' +
                '</svg>',
        };
    }

    /**
     * Returns true to notify the core that read-only mode is supported
     *
     * @return {boolean}
     */
    static get isReadOnlySupported() {
        return true;
    }

    /**
     * @returns {boolean}
     */
    static get enableLineBreaks() {
        return false;
    }

    constructor({ data }) {
        this.data = data;
    }

    /**
     * @param blockContent
     * @returns {{text: string, link: string}}
     */
    save(blockContent) {
        const labelInput = blockContent.querySelector('input[name="label"]');
        const urlInput = blockContent.querySelector('input[name="url"]');

        return {
            text: labelInput.value,
            link: urlInput.value,
        };
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add(
            'grid',
            'grid-cols-2',
            'gap-x-4',
            'gap-y-1',
            'border-2',
            'border-blue',
            'p-2',
            'rounded-md'
        );

        const labelLabel = document.createElement('label');
        labelLabel.classList.add('label');
        labelLabel.innerHTML = 'Button Label';
        wrapper.appendChild(labelLabel);

        const urlLabel = document.createElement('label');
        urlLabel.classList.add('label');
        urlLabel.innerHTML = 'Button URL';
        wrapper.appendChild(urlLabel);

        const labelInput = document.createElement('input');
        labelInput.classList.add('input', 'min-h-4', 'p-2');
        labelInput.setAttribute('name', 'label');
        labelInput.setAttribute('placeholder', 'Label');
        labelInput.value = this.data && this.data.text ? this.data.text : '';

        const urlInput = document.createElement('input');
        urlInput.classList.add('input', 'min-h-4', 'p-2');
        urlInput.setAttribute('name', 'url');
        urlInput.setAttribute('placeholder', 'https://mailcoach.app');
        urlInput.setAttribute('type', 'url');
        urlInput.value = this.data && this.data.link ? this.data.link : '';

        wrapper.appendChild(labelInput);
        wrapper.appendChild(urlInput);

        return wrapper;
    }
}
