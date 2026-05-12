import { useEffect, useState } from 'react';

/** App is light-mode only; kept for API compatibility with existing components. */
export type Appearance = 'light';

const applyLightTheme = (): void => {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('appearance', 'light');
};

export function initializeTheme(): void {
    applyLightTheme();
}

export function useAppearance() {
    const [appearance] = useState<Appearance>('light');

    useEffect(() => {
        applyLightTheme();
    }, []);

    const updateAppearance = (_mode: Appearance) => {
        applyLightTheme();
    };

    return { appearance, updateAppearance };
}
