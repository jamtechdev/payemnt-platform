import * as Dialog from '@radix-ui/react-dialog';

interface Props {
    isOpen: boolean;
    title: string;
    message: string;
    onConfirm: () => void;
    onCancel: () => void;
    confirmLabel?: string;
    confirmVariant?: 'danger' | 'primary';
}

export default function ConfirmModal({
    isOpen,
    title,
    message,
    onConfirm,
    onCancel,
    confirmLabel = 'Confirm',
    confirmVariant = 'danger',
}: Props) {
    return (
        <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onCancel()}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 bg-black/40" />
                <Dialog.Content className="fixed left-1/2 top-1/2 w-[90vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded bg-white p-4 shadow">
                    <Dialog.Title className="text-lg font-semibold">{title}</Dialog.Title>
                    <Dialog.Description className="mt-2 text-sm text-neutral-600">{message}</Dialog.Description>
                    <div className="mt-4 flex justify-end gap-2">
                        <button className="rounded border px-3 py-2 text-sm" onClick={onCancel}>Cancel</button>
                        <button
                            className={`rounded px-3 py-2 text-sm text-white ${confirmVariant === 'danger' ? 'bg-red-600' : 'bg-blue-600'}`}
                            onClick={onConfirm}
                        >
                            {confirmLabel}
                        </button>
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
