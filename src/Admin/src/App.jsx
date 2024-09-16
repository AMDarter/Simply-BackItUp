import { useEffect, useState } from "react";
import "./App.css";
import { Grid, Box } from "@chakra-ui/react";
import { LastBackupProvider, useLastBackup } from "./context/LastBackupContext";
import { ChakraProvider } from "@chakra-ui/react";
import { SettingsForm, DownloadBackup } from "./Components/allComponents";
import { AlertProvider } from "./context/AlertContext";

function BackupSection({ data }) {
	const { lastBackupTime } = useLastBackup();

	return (
		<Box>
			{lastBackupTime && (
				<Box>
					<p>Last backup: {lastBackupTime}</p>
				</Box>
			)}

			{/* Download Backup Button */}
			<DownloadBackup
				ajaxUrl={data?.ajaxurl}
				nonce={data?.nonce}
			/>
		</Box>
	);
}

function App({ data }) {
	return (
		<div className="wrap">
			<ChakraProvider>
				<AlertProvider>
					<LastBackupProvider
						defaultLastBackupTime={data?.settings?.lastBackupTime || null}
					>
						{/* Grid with columns that stack on smaller screens */}
						<Grid
							templateColumns={{ base: "1fr", md: "repeat(3, 1fr)" }}
							gap={8}
						>
							<Box>
								<h1>
									<strong>Simply BackItUp</strong>
								</h1>
								<p>Backup your WordPress site with ease.</p>
								<BackupSection data={data} />
							</Box>
							<Box>
								<SettingsForm
									settings={data?.settings}
									ajaxUrl={data?.ajaxurl}
									nonce={data?.nonce}
								/>
							</Box>
							<Box>
								{/* Upgrade to Premium */}
								<h2>Upgrade to Premium</h2>
								<p>Coming soon...</p>
								<p>Unlock more features with Simply BackItUp Premium.</p>
							</Box>
						</Grid>
					</LastBackupProvider>
				</AlertProvider>
			</ChakraProvider>
		</div>
	);
}

export default App;
