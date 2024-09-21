import "./App.css";
import { ChakraProvider } from "@chakra-ui/react";
import { AlertProvider } from "./context/AlertContext";
import { Dashboard } from "./Components/allComponents";
import { SettingsProvider } from "./context/SettingsContext";
import { BackupHistoryProvider } from "./context/BackupHistoryContext";

function App() {
	return (
		<div
			style={{
				backgroundColor: "#F7FAFC",
				borderRadius: "5px",
			}}
		>
			<ChakraProvider>
				<SettingsProvider>
					<AlertProvider>
						<BackupHistoryProvider>
							<Dashboard />
						</BackupHistoryProvider>
					</AlertProvider>
				</SettingsProvider>
			</ChakraProvider>
		</div>
	);
}

export default App;
