interface ToggleProps {
    checked: boolean;
    onChange: (value: boolean) => void;
    disabled?: boolean;
    label?: string;
}

export default function Toggle({ checked, onChange, disabled = false, label }: ToggleProps): React.JSX.Element {
    const id = label ? `toggle-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined;

    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            id={id}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={[
                'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-[#0d0f14]',
                checked ? 'bg-indigo-600' : 'bg-gray-700',
                disabled ? 'cursor-not-allowed opacity-50' : '',
            ].join(' ')}
        >
            <span
                className={[
                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    checked ? 'translate-x-4' : 'translate-x-0',
                ].join(' ')}
            />
        </button>
    );
}
