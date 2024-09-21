import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useAjax } from './AjaxContext';

const SettingsContext = createContext();

export const useSettings = () => useContext(SettingsContext);

export const SettingsProvider = ({ children }) => {
    const { ajaxUrl, nonce } = useAjax();
    const [settings, setSettings] = useState(null);

    const fetchSettings = useCallback(() => {
        const formData = new FormData();
        formData.append('action', 'simply_backitup_all_settings');
        formData.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((json) => {
                console.log('Settings fetched:', json);
                if (json.success && json.data) {
                    setSettings(json.data);
                } else {
                    console.error('Failed to fetch settings:', json?.data || {});
                }
            })
            .catch((error) => console.error('Error fetching settings:', error));
    }, []);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    return (
        <SettingsContext.Provider value={{ settings, fetchSettings }}>
            {children}
        </SettingsContext.Provider>
    );
};
