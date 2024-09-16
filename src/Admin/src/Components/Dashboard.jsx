import { useEffect, useState } from "react";
import {
    HStack,
	Grid,
	GridItem,
	Box,
	Button,
	Text,
	Heading,
	Center,
} from "@chakra-ui/react";
import { useLastBackup } from "../context/LastBackupContext";
import { SettingsForm, DownloadBackup, SVGLogo } from "./allComponents";

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

const TopSection = ({ data }) => {
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

const BackupSection = ({ data }) => {
	const { lastBackupTime } = useLastBackup();

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
				Backup Status
			</Heading>
			{lastBackupTime ? (
				<Text
					color="gray.600"
					mb={4}
					textAlign={"center"}
				>
					Last backup: <strong>{lastBackupTime}</strong>
				</Text>
			) : (
				<Text
					color="gray.600"
					mb={4}
					textAlign={"center"}
				>
					No backups have been made yet.
				</Text>
			)}

			{/* Download Backup Button */}
			<DownloadBackup
				ajaxUrl={data?.ajaxurl}
				nonce={data?.nonce}
			/>
		</WidgetBox>
	);
};

const SettingsFormSection = ({ data }) => {
	return (
		<WidgetBox>
			<SettingsForm
				settings={data?.settings}
				ajaxUrl={data?.ajaxurl}
				nonce={data?.nonce}
			/>
		</WidgetBox>
	);
};

const UpgradeToPremiumSection = () => {
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
				Upgrade to Premium
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
				Unlock more features with Simply BackItUp Premium.
			</Text>
			<Box textAlign={"center"}>
				<Button
					size="sm"
					colorScheme="teal"
					variant="solid"
					mt={3}
				>
					Notify Me
				</Button>
			</Box>
		</WidgetBox>
	);
};

const Dashboard = ({ data }) => {
	return (
		<>
			<TopSection data={data} />
			{/* Grid with columns that stack on smaller screens */}
			<Grid
				templateColumns={{ base: "1fr", md: "repeat(3, 1fr)" }}
				gap={0}
			>
				<GridItem>
					<BackupSection data={data} />
				</GridItem>
				<GridItem>
					<SettingsFormSection data={data} />
				</GridItem>
				<GridItem>
					<UpgradeToPremiumSection />
				</GridItem>
			</Grid>
		</>
	);
};

export default Dashboard;
