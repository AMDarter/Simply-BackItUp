import "./App.css";
import { LastBackupProvider } from "./context/LastBackupContext";
import { ChakraProvider } from "@chakra-ui/react";
import { AlertProvider } from "./context/AlertContext";
import { Dashboard } from "./Components/allComponents";

function App({ data }) {
	return (
		<div
			className="wrap"
			style={{
				backgroundColor: "#F7FAFC",
				borderRadius: "5px",
			}}
		>
			<ChakraProvider>
				<AlertProvider>
					<LastBackupProvider
						defaultLastBackupTime={data?.settings?.lastBackupTime || null}
					>
						<Dashboard data={data} />
					</LastBackupProvider>
				</AlertProvider>
			</ChakraProvider>
		</div>
	);
}

export default App;
