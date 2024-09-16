import { createContext, useContext, useState } from "react";

const LastBackupContext = createContext(undefined);

export const LastBackupProvider = ({
	children,
	defaultLastBackupTime = null,
}) => {
	const [lastBackupTime, setLastBackupTime] = useState(defaultLastBackupTime);

	return (
		<LastBackupContext.Provider
			value={{
				lastBackupTime: lastBackupTime,
				setLastBackupTime: setLastBackupTime,
			}}
		>
			{children}
		</LastBackupContext.Provider>
	);
};

export const useLastBackup = () => useContext(LastBackupContext);
