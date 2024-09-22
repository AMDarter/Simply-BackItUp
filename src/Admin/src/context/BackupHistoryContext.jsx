import React, { createContext, useContext, useState, useEffect } from "react";
import { useAjax } from "./AjaxContext";

const BackupHistoryContext = createContext();

export const useBackupHistory = () => useContext(BackupHistoryContext);

export const BackupHistoryProvider = ({ children }) => {
	const [backupHistory, setBackupHistory] = useState(null);
	const { ajaxUrl, nonce } = useAjax();

	const fetchBackups = async () => {
		const formData = new FormData();
		formData.append("action", "simply_backitup_all_history");
		formData.append("nonce", nonce);
		formData.append("allHistory", true);

		try {
			const response = await fetch(ajaxUrl, {
				method: "POST",
				body: formData,
			});

			if (!response.ok) {
				throw new Error("Failed to fetch backup history");
			}

			const json = await response.json();
			setBackupHistory(json?.data?.history || []);
		} catch (error) {
			console.error("Error fetching backup history", error);
		}
	};

    const clearBackups = async () => {
        const formData = new FormData();
        formData.append("action", "simply_backitup_clear_history");
        formData.append("nonce", nonce);

        try {
            const response = await fetch(ajaxUrl, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error("Failed to clear backup history");
            }

            fetchBackups();
        } catch (error) {
            console.error("Error clearing backup history", error);
        }
    };

	useEffect(() => {
		fetchBackups();

		// Fetch backups every 2 minutes.
		const fetchBackupsInterval = setInterval(fetchBackups, 120000);

		return () => clearInterval(fetchBackupsInterval);
	}, [ajaxUrl, nonce]);

	return (
		<BackupHistoryContext.Provider value={{ backupHistory, fetchBackups, clearBackups }}>
			{children}
		</BackupHistoryContext.Provider>
	);
};
