import { useState } from "react";
import {
	HStack,
	Grid,
	GridItem,
	Box,
	Button,
	Text,
	Heading,
	Center,
	Table,
	Tr,
	Th,
	Td,
	TableContainer,
	Thead,
	Tbody,
	Spinner,
} from "@chakra-ui/react";
import { SettingsForm, DownloadBackup, SVGLogo } from "./allComponents";
import { useBackupHistory } from "../context/BackupHistoryContext";
import useTimeoutManager from "../hooks/useTimeoutManager";

const WidgetBox = ({ children, ...props }) => {
	return (
		<Box p={3}>
			<Box
				bg="white"
				p={6}
				borderRadius="md"
				boxShadow="sm"
				{...props}
			>
				{children}
			</Box>
		</Box>
	);
};

const TopSection = () => {
	return (
		<WidgetBox>
			<Grid
				templateColumns={{ base: "1fr", md: "repeat(2, 1fr)" }}
				gap={0}
			>
				<GridItem>
					<HStack>
						<Center>
							<span>
								<SVGLogo width="50px" />
							</span>
							<span>
								<Heading
									as="h2"
									mb={2}
									mt={0}
									style={{
										marginTop: "0px",
										fontSize: "22px",
										fontWeight: "bold",
										marginLeft: "10px",
									}}
									color="#082C44"
								>
									Dashboard
								</Heading>
							</span>
						</Center>
					</HStack>
				</GridItem>
				<GridItem></GridItem>
			</Grid>
		</WidgetBox>
	);
};

const DownloadSNowSection = () => {
	return (
		<WidgetBox>
			<Heading
				as="h2"
				mb={2}
				mt={0}
				color="#082C44"
				textAlign={"center"}
				style={{
					fontSize: "22px",
					fontWeight: "bold",
				}}
			>
				Download a Backup
			</Heading>
			<Text
				fontSize="sm"
				color="gray.600"
				mb={2}
				textAlign={"center"}
			>
				Export a new backup at this moment.
			</Text>
			<DownloadBackup />
		</WidgetBox>
	);
};

const BackupHistorySection = () => {
	const { backupHistory, fetchBackups, clearBackups } = useBackupHistory();
	const [refreshing, setRefreshing] = useState(false);
	const { set: setTimeout } = useTimeoutManager();

	const refreshBackups = (e) => {
		e.preventDefault();
		setRefreshing(true);
		fetchBackups();
		setTimeout(() => setRefreshing(false), 2000);
	};

	return (
		<WidgetBox>
			<Center>
				<Heading
					as="h2"
					mb={2}
					mt={0}
					color="#082C44"
					textAlign={"center"}
					style={{
						fontSize: "22px",
						fontWeight: "bold",
					}}
				>
					Backup History
				</Heading>
				<span>
					<Button
						type="button"
						aria-label="Refresh"
						size="xs"
						colorScheme="blue"
						variant="solid"
						onClick={refreshBackups}
						isLoading={refreshing}
						style={{ marginLeft: "10px", width: "25px", height: "25px" }}
					>
						<span
							class="dashicons dashicons-update"
							aria-hidden="true"
						></span>
					</Button>
					<Button
						type="button"
						aria-label="Clear"
						size="xs"
						colorScheme="red"
						variant="solid"
						onClick={clearBackups}
						isLoading={refreshing}
						style={{ marginLeft: "10px", width: "25px", height: "25px" }}
					>
						<span
							class="dashicons dashicons-trash"
							aria-hidden="true"
						></span>
					</Button>
				</span>
			</Center>
			<Box
				border="1px solid #E2E8F0"
				borderRadius="5px"
				p={0}
				style={{
					overflowX: "hidden",
				}}
			>
				{backupHistory === null && (
					<Center minHeight="100px">
						<Spinner size="lg" />
					</Center>
				)}
				{backupHistory &&
					Array.isArray(backupHistory) &&
					backupHistory.length > 0 && (
						<TableContainer
							style={{
								overflowY: "scroll",
								maxHeight: "300px",
							}}
						>
							<Table size="sm">
								<Thead
									style={{
										position: "sticky",
										top: "0",
										backgroundColor: "#fff",
										boxShadow: "0 2px 2px -1px rgba(0, 0, 0, 0.1)",
										border: "none",
									}}
								>
									<Tr>
										<Th>Date</Th>
										<Th>Message</Th>
									</Tr>
								</Thead>
								<Tbody>
									{backupHistory.map((backup) => (
										<Tr key={backup.date}>
											<Td>
												<span style={{ fontSize: "12px" }}>{backup.date}</span>
											</Td>
											<Td>
												<span style={{ fontSize: "12px" }}>
													{backup.message}
												</span>
											</Td>
										</Tr>
									))}
								</Tbody>
							</Table>
						</TableContainer>
					)}
				{backupHistory &&
					Array.isArray(backupHistory) &&
					backupHistory.length === 0 && (
						<Text textAlign="center">
							No backups found. Make a backup to see history.
						</Text>
					)}
			</Box>
		</WidgetBox>
	);
};

const SettingsFormSection = () => {
	return (
		<WidgetBox>
			<SettingsForm />
		</WidgetBox>
	);
};

const AddonsSection = () => {
	return (
		<WidgetBox>
			<Heading
				as="h2"
				mb={2}
				mt={0}
				style={{
					marginTop: "0px",
					fontSize: "22px",
					fontWeight: "bold",
				}}
				color="teal.600"
				textAlign={"center"}
			>
				Extend with Add-ons
			</Heading>
			<Text
				fontSize="md"
				color="gray.600"
				mb={4}
				textAlign={"center"}
			>
				Coming soon...
			</Text>
			<Text
				fontSize="md"
				color="gray.600"
				mb={4}
				textAlign={"center"}
			>
				Unlock more features with Simply BackItUp Add-ons.
			</Text>
			{/* <Box textAlign={"center"}>
				<Button
					size="sm"
					colorScheme="teal"
					variant="solid"
					mt={3}
				>
					See Add-ons
				</Button>
			</Box> */}
		</WidgetBox>
	);
};

const Dashboard = () => {
	return (
		<>
			<TopSection />
			{/* Grid with columns that stack on smaller screens */}
			<Grid
				templateColumns={{ base: "1fr", md: "repeat(2, 1fr)" }}
				gap={0}
			>
				<GridItem>
					<SettingsFormSection />
				</GridItem>
				<GridItem>
					<DownloadSNowSection />
					<BackupHistorySection />
					<AddonsSection />
				</GridItem>
			</Grid>
		</>
	);
};

export default Dashboard;
