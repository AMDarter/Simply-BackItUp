import React, { useState, useEffect } from "react";
import { BackupProgressBar, TestConnectionButton } from "./allComponents";
import {
	Box,
	Button,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Input,
	SimpleGrid,
	Heading,
	Spinner,
	Center,
} from "@chakra-ui/react";
import {
	recursiveAppendFormData,
	render24HourTimeOptions,
	performBackupStep,
	submitFormData,
} from "../utils/formUtils";
import { useAlert } from "../context/AlertContext";
import useTimeoutManager from "../hooks/useTimeoutManager";
import { useAjax } from "../context/AjaxContext";
import { useSettings } from "../context/SettingsContext";
import { useBackupHistory } from "../context/BackupHistoryContext";

const defaultBackupStorageCredentials = {
	googleDriveApiKey: "",
	googleDriveClientId: "",
	googleDriveClientSecret: "",
	dropboxAccessToken: "",
	oneDriveClientId: "",
	oneDriveClientSecret: "",
	amazonS3AccessKey: "",
	amazonS3SecretKey: "",
	amazonS3BucketName: "",
	amazonS3Region: "",
	ftpHost: "",
	ftpUsername: "",
	ftpPassword: "",
	ftpPort: "21",
};

const SettingsForm = () => {
	const { ajaxUrl, nonce } = useAjax();
	const { settings, fetchSettings } = useSettings();
	const [formValues, setFormValues] = useState(null);
	const [errors, setErrors] = useState({});
	const { createAlert } = useAlert();
	const [backupProgress, setBackupProgress] = useState(null);
	const [savingSettings, setSavingSettings] = useState(false);
	const [runningBackup, setRunningBackup] = useState(false);
	const { set: setTimeout } = useTimeoutManager();
	const { fetchBackups } = useBackupHistory();

	const storageLocations = [
		{ value: "FTP", label: "FTP", enabled: true },
		{ value: "Amazon S3", label: "Amazon S3", enabled: true },
		{ value: "Google Drive", label: "Google Drive", enabled: false },
		{ value: "Dropbox", label: "Dropbox", enabled: false },
		{ value: "OneDrive", label: "OneDrive", enabled: false },
	];

	useEffect(() => {
		if (settings && Object.keys(settings).length > 0 && !formValues) {
			setFormValues({
				backupFrequency: settings.backupFrequency || "daily",
				backupTime: settings.backupTime || "03:00",
				backupEmail: settings.backupEmail || "",
				backupStorageLocation: settings.backupStorageLocation || "",
				backupStorageCredentials: {
					...defaultBackupStorageCredentials,
					...settings.backupStorageCredentials,
				},
				backupFiles: settings.backupFiles || true,
				backupDatabase: settings.backupDatabase || true,
				backupPlugins: settings.backupPlugins || true,
				backupThemes: settings.backupThemes || true,
				backupUploads: settings.backupUploads || true,
			});
		}
	}, [settings]);

	const validateForm = () => {
		const errors = {};

		if (!formValues.backupFrequency) {
			errors.backupFrequency = "Backup Frequency is required";
		}

		if (!formValues.backupTime) {
			errors.backupTime = "Backup Time is required";
		}

		if (
			formValues.backupEmail &&
			!/\S+@\S+\.\S+/.test(formValues.backupEmail)
		) {
			errors.backupEmail = "Invalid email address";
		}

		if (!formValues.backupStorageLocation) {
			errors.backupStorageLocation = "Storage Location is required";
		}

		const credentials = formValues.backupStorageCredentials;
		switch (formValues.backupStorageLocation) {
			case "Google Drive":
				if (!credentials.googleDriveApiKey) {
					errors.googleDriveApiKey = "Google Drive API Key is required";
				}
				if (!credentials.googleDriveClientId) {
					errors.googleDriveClientId = "Google Drive Client ID is required";
				}
				if (!credentials.googleDriveClientSecret) {
					errors.googleDriveClientSecret =
						"Google Drive Client Secret is required";
				}
				break;
			case "Dropbox":
				if (!credentials.dropboxAccessToken) {
					errors.dropboxAccessToken = "Dropbox Access Token is required";
				}
				break;
			case "OneDrive":
				if (!credentials.oneDriveClientId) {
					errors.oneDriveClientId = "OneDrive Client ID is required";
				}
				if (!credentials.oneDriveClientSecret) {
					errors.oneDriveClientSecret = "OneDrive Client Secret is required";
				}
				break;
			case "Amazon S3":
				if (!credentials.amazonS3AccessKey) {
					errors.amazonS3AccessKey = "Amazon S3 Access Key is required";
				}
				if (!credentials.amazonS3SecretKey) {
					errors.amazonS3SecretKey = "Amazon S3 Secret Key is required";
				}
				if (!credentials.amazonS3BucketName) {
					errors.amazonS3BucketName = "Amazon S3 Bucket Name is required";
				}
				if (!credentials.amazonS3Region) {
					errors.amazonS3Region = "Amazon S3 Region is required";
				}
				break;
			case "FTP":
				if (!credentials.ftpHost) {
					errors.ftpHost = "FTP Host is required";
				}
				if (!credentials.ftpUsername) {
					errors.ftpUsername = "FTP Username is required";
				}
				if (!credentials.ftpPassword) {
					errors.ftpPassword = "FTP Password is required";
				}
				if (!credentials.ftpPort) {
					errors.ftpPort = "FTP Port is required";
				}
				break;
			default:
				break;
		}

		setErrors(errors);
		return Object.keys(errors).length === 0;
	};

	const handleInputChange = (event) => {
		const { name, type, value, checked } = event.target;
		setFormValues((prevValues) => ({
			...prevValues,
			[name]: type === "checkbox" ? checked : value,
		}));
	};

	const handleCredentialChange = (event) => {
		const { name, value } = event.target;
		setFormValues((prevValues) => ({
			...prevValues,
			backupStorageCredentials: {
				...prevValues.backupStorageCredentials,
				[name]: value,
			},
		}));
	};

	const handleSaveSettings = async (e) => {
		e.preventDefault();
		setSavingSettings(true);

		const saveResult = await handleSubmit();

		if (saveResult.success) {
			createAlert("Settings saved successfully.", "success");
			fetchSettings(); // Fetch settings to update the context.
		} else {
			createAlert(
				saveResult.message || "An error occurred while saving settings.",
				"error"
			);
			if (saveResult.validationErrors) {
				setErrors((prevErrors) => ({
					...prevErrors,
					...saveResult.validationErrors,
				}));
			}
		}

		setSavingSettings(false);
	};

	const handleSubmit = async () => {
		if (savingSettings) {
			return {
				success: false,
				message: "Settings are being saved. Cannot save again.",
			};
		}

		if (!validateForm()) {
			return {
				success: false,
				message: "The form contains errors. Please correct them and try again.",
			};
		}

		const formData = new FormData();
		formData.append("action", "simply_backitup_save_settings");
		formData.append("nonce", nonce);

		for (const [key, value] of Object.entries(formValues)) {
			recursiveAppendFormData(formData, key, value);
		}

		const { success, data, error } = await submitFormData(ajaxUrl, formData);

		if (success) {
			return { success: true };
		} else {
			return {
				success: false,
				message: data?.data?.message || error,
				validationErrors: data?.data?.validationErrors,
			};
		}
	};

	const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

	const handleBackupNow = async () => {
		let backupProgressResult;

		setBackupProgress({ value: 0, message: "Starting backup..." });

		// Step 0
		await Promise.all([
			wait(1500).then(() =>
				setBackupProgress({ value: 5, message: "Zipping files..." })
			),
			performBackupStep({
				ajaxUrl,
				action: "simply_backitup_step0",
				nonce: nonce,
			}),
		]).then(([_, result]) => {
			backupProgressResult = result;
		});

        console.log(backupProgressResult);
		if (!backupProgressResult.success) {
			return { success: false, message: backupProgressResult.message };
		}

		setBackupProgress({ value: 10, message: backupProgressResult.progress.message });
		backupProgressResult = null;

		// Step 1
		await Promise.all([
			wait(1500).then(() =>
				setBackupProgress({ value: 30, message: "Zipping files..." })
			),
			performBackupStep({
				ajaxUrl,
				action: "simply_backitup_step1",
				nonce: nonce,
			}),
		]).then(([_, result]) => {
			backupProgressResult = result;
		});

        console.log(backupProgressResult);
		if (!backupProgressResult.success) {
			return { success: false, message: backupProgressResult.message };
		}

		setBackupProgress({ value: 50, message: backupProgressResult.progress.message });
		backupProgressResult = null;

		// Step 2
		await Promise.all([
			wait(1500).then(() =>
				setBackupProgress({ value: 55, message: "Exporting database..." })
			),
			performBackupStep({
				ajaxUrl,
				action: "simply_backitup_step2",
				nonce: nonce,
			}),
		]).then(([_, result]) => {
			backupProgressResult = result;
		});

        console.log(backupProgressResult);
		if (!backupProgressResult.success) {
			return { success: false, message: backupProgressResult.message };
		}

		setBackupProgress({ value: 75, message: backupProgressResult.progress.message });
		backupProgressResult = null;

		// Step 3
		await Promise.all([
			wait(1500).then(() =>
				setBackupProgress({
					value: 80,
					message: "Uploading backup to cloud server...",
				})
			),
			performBackupStep({
				ajaxUrl,
				action: "simply_backitup_step3",
				nonce: nonce,
			}),
		]).then(([_, result]) => {
			backupProgressResult = result;
		});

        console.log(backupProgressResult);
		if (!backupProgressResult.success) {
			return { success: false, message: backupProgressResult.message };
		}

		setBackupProgress({ value: 100, message: backupProgressResult.progress.message }); // 100% progress
		return { success: true };
	};

	const handleSaveAndBackupNow = async (e) => {
		e.preventDefault();

		setRunningBackup(true);
		setSavingSettings(true);

		const saveResult = await handleSubmit();

		if (!saveResult.success) {
			createAlert(
				saveResult.message || "An error occurred while saving settings.",
				"error"
			);
			if (saveResult.validationErrors) {
				setErrors((prevErrors) => ({
					...prevErrors,
					...saveResult.validationErrors,
				}));
			}
			setSavingSettings(false);
			setRunningBackup(false);
			return;
		}

		createAlert(
			"Settings saved successfully. Proceeding with backup...",
			"success"
		);

		const backupResult = await handleBackupNow();

		if (backupResult.success) {
			createAlert("Backup completed successfully.", "success");
		} else {
			createAlert(
				backupResult.message || "An error occurred during backup.",
				"error"
			);
		}

		fetchBackups(); // Refresh backup history after backup
		fetchSettings(); // Fetch settings to update the context.

		setTimeout(() => {
			setSavingSettings(false);
			setBackupProgress(null);
			setRunningBackup(false);
		}, 1000);
	};

	const renderCredentialFields = (location) => {
		switch (location) {
			case "Google Drive":
				return renderGoogleDriveFields();
			case "Dropbox":
				return renderDropboxFields();
			case "OneDrive":
				return renderOneDriveFields();
			case "Amazon S3":
				return renderAmazonS3Fields();
			case "FTP":
				return renderFTPFields();
			default:
				return null;
		}
	};

	const renderGoogleDriveFields = () => (
		<>
			<FormControl
				as="fieldset"
				mt={3}
			>
				<TestConnectionButton
					platform="Google Drive"
					disabled={savingSettings || runningBackup}
					onClick={() => {
						console.log("Test Google Drive Connection");
					}}
				></TestConnectionButton>
			</FormControl>
			{renderCredentialInput("Google Drive API Key", "googleDriveApiKey")}
			{renderCredentialInput("Google Drive Client ID", "googleDriveClientId")}
			{renderCredentialInput(
				"Google Drive Client Secret",
				"googleDriveClientSecret"
			)}
		</>
	);

	const renderDropboxFields = () => (
		<>
			<FormControl
				as="fieldset"
				mt={3}
			>
				<TestConnectionButton
					platform="Dropbox"
					disabled={savingSettings || runningBackup}
					onClick={() => {
						console.log("Test Dropbox Connection");
					}}
				></TestConnectionButton>
			</FormControl>
			{renderCredentialInput("Dropbox Access Token", "dropboxAccessToken")}
		</>
	);

	const renderOneDriveFields = () => (
		<>
			<FormControl
				as="fieldset"
				mt={3}
			>
				<TestConnectionButton
					platform="OneDrive"
					disabled={savingSettings || runningBackup}
					onClick={() => {
						console.log("Test OneDrive Connection");
					}}
				></TestConnectionButton>
			</FormControl>
			{renderCredentialInput("OneDrive Client ID", "oneDriveClientId")}
			{renderCredentialInput("OneDrive Client Secret", "oneDriveClientSecret")}
		</>
	);

	const renderAmazonS3Fields = () => (
		<>
			<FormControl
				as="fieldset"
				mt={3}
			>
				<TestConnectionButton
					platform="Amazon S3"
					disabled={savingSettings || runningBackup}
					onClick={() => {
						console.log("Test Amazon S3 Connection");
					}}
				></TestConnectionButton>
			</FormControl>
			{renderCredentialInput("Amazon S3 Access Key", "amazonS3AccessKey")}
			{renderCredentialInput("Amazon S3 Secret Key", "amazonS3SecretKey")}
			{renderCredentialInput("Amazon S3 Bucket Name", "amazonS3BucketName")}
			{renderCredentialInput("Amazon S3 Region", "amazonS3Region")}
		</>
	);

	const renderFTPFields = () => (
		<>
			<FormControl
				as="fieldset"
				mt={3}
			>
				<TestConnectionButton
					platform="FTP"
					disabled={savingSettings || runningBackup}
					onClick={() => {
						console.log("Test FTP Connection");
					}}
				></TestConnectionButton>
			</FormControl>
			{renderCredentialInput("FTP Host", "ftpHost")}
			{renderCredentialInput("FTP Username", "ftpUsername")}
			{renderCredentialInput("FTP Password", "ftpPassword")}
			{renderCredentialInput("FTP Port", "ftpPort", "21")}
		</>
	);

	const renderCredentialInput = (labelText, inputId) => (
		<FormControl
			as="fieldset"
			mt={3}
			isInvalid={!!errors[inputId]}
		>
			<FormLabel>{labelText}</FormLabel>
			<Input
				type="text"
				name={inputId}
				disabled={savingSettings || runningBackup}
				value={formValues.backupStorageCredentials[inputId]}
				onChange={handleCredentialChange}
				onBlur={() => clearErrorForField(inputId)}
				borderColor="gray.300"
				focusBorderColor="blue.500"
				style={{
					width: "100%",
					maxWidth: "26rem",
					padding: "0 8px",
					height: "2rem",
					borderRadius: "4px",
					border: "1px solid #CBD5E0",
				}}
			/>
			<FormErrorMessage>{errors[inputId]}</FormErrorMessage>
		</FormControl>
	);

	const resetBackupStorageCredentials = () => {
		setFormValues({
			...formValues,
			backupStorageCredentials: {
				...defaultBackupStorageCredentials,
				...settings.backupStorageCredentials,
			},
		});
	};

	const clearErrorForField = (field) => {
		if (errors[field]) {
			setErrors((prevErrors) => ({
				...prevErrors,
				[field]: "",
			}));
		}
	};

	if (!formValues) {
		return (
			<Center minHeight="100px">
				<Spinner size="lg" />
			</Center>
		);
	}

	return (
		<>
			<Box mb={4}>
				<Heading
					as="h2"
					mb={2}
					mt={0}
					color="#082C44"
					style={{
						marginTop: "0px",
						fontSize: "22px",
						fontWeight: "bold",
						color: "#2D3748",
					}}
				>
					Backup Settings
				</Heading>
				<hr />
			</Box>

			<form>
				<SimpleGrid
					columns={2}
					spacing={10}
				>
					{/* Frequency Setting */}
					<FormControl
						as="fieldset"
						mt={4}
						isInvalid={!!errors.backupFrequency}
					>
						<FormLabel fontWeight="medium">Backup Frequency</FormLabel>
						<select
							name="backupFrequency"
							value={formValues.backupFrequency}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupFrequency")}
							style={{
								width: "100%",
								maxWidth: "26rem",
								padding: "0 8px",
								height: "2rem",
								borderRadius: "4px",
								border: "1px solid #CBD5E0",
							}}
						>
							<option value="daily">Daily</option>
							<option value="weekly">Weekly</option>
							<option value="monthly">Monthly</option>
						</select>
						<FormErrorMessage>{errors.backupFrequency}</FormErrorMessage>
					</FormControl>

					{/* Time Setting */}
					<FormControl
						as="fieldset"
						mt={4}
						isInvalid={!!errors.backupTime}
					>
						<FormLabel fontWeight="medium">Backup Time</FormLabel>
						<select
							name="backupTime"
							value={formValues.backupTime}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupTime")}
							style={{
								width: "100%",
								maxWidth: "26rem",
								padding: "0 8px",
								height: "2rem",
								borderRadius: "4px",
								border: "1px solid #CBD5E0",
							}}
						>
							{render24HourTimeOptions()}
						</select>
						<FormErrorMessage>{errors.backupTime}</FormErrorMessage>
					</FormControl>
				</SimpleGrid>
				<SimpleGrid
					columns={2}
					spacing={10}
				>
					{/* Email Setting */}
					<FormControl
						as="fieldset"
						mt={4}
						isInvalid={!!errors.backupEmail}
					>
						<FormLabel fontWeight="medium">Backup Email</FormLabel>
						<Input
							type="text"
							name="backupEmail"
							value={formValues.backupEmail}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupEmail")}
							borderColor="gray.300"
							focusBorderColor="blue.500"
							style={{
								width: "100%",
								maxWidth: "26rem",
								padding: "0 8px",
								height: "2rem",
								borderRadius: "4px",
								border: "1px solid #CBD5E0",
							}}
						/>
						<FormErrorMessage>{errors.backupEmail}</FormErrorMessage>
					</FormControl>

					{/* Storage Location Setting */}
					<FormControl
						as="fieldset"
						mt={4}
						isInvalid={!!errors.backupStorageLocation}
					>
						<FormLabel fontWeight="medium">Storage Location</FormLabel>
						<select
							name="backupStorageLocation"
							value={formValues.backupStorageLocation}
							disabled={savingSettings || runningBackup}
							onChange={(e) => {
								resetBackupStorageCredentials();
								handleInputChange(e);
							}}
							onBlur={() => clearErrorForField("backupStorageLocation")}
							style={{
								width: "100%",
								maxWidth: "26rem",
								padding: "0 8px",
								height: "2rem",
								borderRadius: "4px",
								border: "1px solid #CBD5E0",
							}}
						>
							<option
								key="option"
								value=""
							>
								Choose a location
							</option>
							{storageLocations.map((location) => {
								if (location.enabled) {
									return (
										<option
											key={location.value}
											value={location.value}
										>
											{location.label}
										</option>
									);
								}
							})}
						</select>
						<FormErrorMessage>{errors.backupStorageLocation}</FormErrorMessage>
					</FormControl>
				</SimpleGrid>

				{/* Render Credential Fields Based on Selection */}
				{renderCredentialFields(formValues.backupStorageLocation)}

				{/* Settings Grid */}
				<SimpleGrid
					columns={[1, 2]}
					spacing={5}
					mt={5}
				>
					{/* Backup Files Setting */}
					<FormControl as="fieldset">
						<FormLabel fontWeight="medium">Backup Files</FormLabel>
						<input
							type="checkbox"
							name="backupFiles"
							checked={formValues.backupFiles}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
						/>
					</FormControl>

					{/* Backup Database Setting */}
					<FormControl as="fieldset">
						<FormLabel fontWeight="medium">Backup Database</FormLabel>
						<input
							type="checkbox"
							name="backupDatabase"
							checked={formValues.backupDatabase}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
						/>
					</FormControl>

					{/* Backup Plugins Setting */}
					<FormControl as="fieldset">
						<FormLabel fontWeight="medium">Backup Plugins</FormLabel>
						<input
							type="checkbox"
							name="backupPlugins"
							checked={formValues.backupPlugins}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
						/>
					</FormControl>

					{/* Backup Themes Setting */}
					<FormControl as="fieldset">
						<FormLabel fontWeight="medium">Backup Themes</FormLabel>
						<input
							type="checkbox"
							name="backupThemes"
							checked={formValues.backupThemes}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
						/>
					</FormControl>

					{/* Backup Uploads Setting */}
					<FormControl as="fieldset">
						<FormLabel fontWeight="medium">Backup Uploads</FormLabel>
						<input
							type="checkbox"
							name="backupUploads"
							checked={formValues.backupUploads}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
						/>
					</FormControl>
				</SimpleGrid>

				{/* Buttons */}
				<Box
					mt={6}
					style={{ minHeight: "60px" }} // Avoids layout shift.
				>
					{!backupProgress && (
						<div style={{ textAlign: "center" }}>
							<Button
								type="button"
								size="sm"
								colorScheme="blue"
								onClick={handleSaveSettings}
								isLoading={savingSettings}
								loadingText="Working"
								m={3}
							>
								Save Settings
							</Button>
							<Button
								type="button"
								size="sm"
								colorScheme="teal"
								onClick={handleSaveAndBackupNow}
								isLoading={runningBackup || backupProgress}
								loadingText="Working"
								m={3}
							>
								Save & Backup Now
							</Button>
						</div>
					)}
					{/* Backup Progress */}
					{backupProgress && <BackupProgressBar {...backupProgress} />}
				</Box>
			</form>
		</>
	);
};

export default SettingsForm;
