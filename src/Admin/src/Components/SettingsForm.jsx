import React, { useState } from "react";
import PropTypes from "prop-types";
import { BackupProgressBar, TestConnectionButton } from "./allComponents";
import {
	Box,
	Button,
	Container,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Input,
} from "@chakra-ui/react";
import {
	recursiveAppendFormData,
	render24HourTimeOptions,
	performBackupStep,
	submitFormData,
} from "../utils/formUtils";
import { useAlert } from "../context/AlertContext";
import useTimeoutManager from "../hooks/useTimeoutManager";
import { useLastBackup } from "../context/LastBackupContext";

const SettingsForm = ({ settings, ajaxUrl, nonce }) => {
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

	const [formValues, setFormValues] = useState({
		backupFrequency: settings.backupFrequency || "daily",
		backupTime: settings.backupTime || "03:00",
		backupEmail: settings.backupEmail || "",
		backupStorageLocation: settings.backupStorageLocation || "",
		backupStorageCredentials: {
			...defaultBackupStorageCredentials,
			...settings.backupStorageCredentials,
		},
	});

	const [errors, setErrors] = useState({});
	const { createAlert } = useAlert();
	const [backupProgress, setBackupProgress] = useState(null);
	const [savingSettings, setSavingSettings] = useState(false);
	const [runningBackup, setRunningBackup] = useState(false);
	const [saveButtonColor, setSaveButtonColor] = useState("primary");
	const [saveAndBackupNowButtonColor, setSaveAndBackupNowButtonColor] =
		useState("secondary");
	const { set: setTimeout } = useTimeoutManager();
	const { setLastBackupTime } = useLastBackup();

	const setButtonColor = (buttonType, color) => {
		if (buttonType === "save") {
			setSaveButtonColor(color);
			setTimeout(() => setSaveButtonColor("primary"), 1000);
		} else if (buttonType === "saveAndBackupNow") {
			setSaveAndBackupNowButtonColor(color);
			setTimeout(() => setSaveAndBackupNowButtonColor("secondary"), 1000);
		}
	};

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
		const { name, value } = event.target;
		setFormValues((prevValues) => ({
			...prevValues,
			[name]: value,
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
			setButtonColor("save", "success");
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
			setButtonColor("save", "error");
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

	const handleBackupNow = async () => {
		let backupProgressResult;

		setBackupProgress({ value: 0, message: "Starting backup..." });

		backupProgressResult = await performBackupStep({
			ajaxUrl,
			action: "simply_backitup_step1",
			nonce: nonce,
			progressValue: 33,
			message: "Creating backup file...",
		});

		if (!backupProgressResult.success) {
			return { success: false, message: "Failed at step 1" };
		}

		setBackupProgress(backupProgressResult.progress);

		backupProgressResult = await performBackupStep({
			ajaxUrl,
			action: "simply_backitup_step2",
			nonce: nonce,
			progressValue: 66,
			message: "Uploading backup file...",
		});

		if (!backupProgressResult.success) {
			return { success: false, message: "Failed at step 2" };
		}

		setBackupProgress(backupProgressResult.progress);

		backupProgressResult = await performBackupStep({
			ajaxUrl,
			action: "simply_backitup_step3",
			nonce: nonce,
			progressValue: 100,
			message: "Backup completed.",
		});

		if (!backupProgressResult.success) {
			return { success: false, message: "Failed at step 3" };
		}

		setBackupProgress(backupProgressResult.progress);
		setLastBackupTime(backupProgressResult.progress.backupTime);
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
			setButtonColor("saveAndBackupNow", "error");
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
			/>
			<FormErrorMessage>{errors[inputId]}</FormErrorMessage>
		</FormControl>
	);

	const resetBackupStorageCredentials = () => {
		setFormValues({
			...formValues,
			backupStorageCredentials: {
				...defaultBackupStorageCredentials,
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

	const handleDownloadBackup = async () => {
		const formData = new FormData();
		formData.append("action", "simply_backitup_download_zip");
		formData.append("nonce", nonce);

		try {
			const response = await fetch(ajaxUrl, {
				method: "POST",
				body: formData,
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
			});

			if (!response.ok) {
				return { success: false, message: "Failed to download backup." };
			}

			const blob = await response.blob();
			const url = window.URL.createObjectURL(blob);
			const a = document.createElement("a");
			a.href = url;
			a.download = "backup.zip";
			document.body.appendChild(a);
			a.click();
			a.remove();
			window.URL.revokeObjectURL(url);

			return { success: true };
		} catch (error) {
			return {
				success: false,
				message: error.message || "An error occurred during backup download.",
			};
		}
	};

	const handleDownloadBackupClick = async (e) => {
		e.preventDefault();

		const result = await handleDownloadBackup();

		if (result.success) {
			createAlert("Backup downloaded successfully.", "success");
		} else {
			createAlert(result.message, "error");
		}
	};

	return (
		<div
			style={{
				position: "relative",
			}}
		>
			<Container
				maxW="lg"
				style={{
					position: "relative",
					padding: "20px",
					backgroundColor: "#f9f9f9",
					borderRadius: "5px",
				}}
			>
				<h2 style={{ marginTop: "0px", fontSize: "20px" }}>Backup Settings</h2>
				<hr />

				<form>
					{/* Frequency Setting */}
					<FormControl
						as="fieldset"
						mt={3}
						isInvalid={!!errors.backupFrequency}
					>
						<FormLabel>Backup Frequency</FormLabel>
						<select
							name="backupFrequency"
							value={formValues.backupFrequency}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupFrequency")}
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
						mt={3}
						isInvalid={!!errors.backupTime}
					>
						<FormLabel>Backup Time</FormLabel>
						<select
							name="backupTime"
							value={formValues.backupTime}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupTime")}
						>
							{render24HourTimeOptions()}
						</select>
						<FormErrorMessage>{errors.backupTime}</FormErrorMessage>
					</FormControl>

					{/* Email Setting */}
					<FormControl
						as="fieldset"
						mt={3}
						isInvalid={!!errors.backupEmail}
					>
						<FormLabel>Backup Email</FormLabel>
						<Input
							type="text"
							name="backupEmail"
							value={formValues.backupEmail}
							disabled={savingSettings || runningBackup}
							onChange={handleInputChange}
							onBlur={() => clearErrorForField("backupEmail")}
						/>
						<FormErrorMessage>{errors.backupEmail}</FormErrorMessage>
					</FormControl>

					{/* Storage Location Setting */}
					<FormControl
						as="fieldset"
						mt={3}
						isInvalid={!!errors.backupStorageLocation}
					>
						<FormLabel>Storage Location</FormLabel>
						<select
							name="backupStorageLocation"
							value={formValues.backupStorageLocation}
							disabled={savingSettings || runningBackup}
							onChange={(e) => {
								resetBackupStorageCredentials();
								handleInputChange(e);
							}}
							onBlur={() => clearErrorForField("backupStorageLocation")}
						>
							<option value="">Select</option>
							<option value="Google Drive">Google Drive</option>
							<option value="Dropbox">Dropbox</option>
							<option value="OneDrive">OneDrive</option>
							<option value="Amazon S3">Amazon S3</option>
							<option value="FTP">FTP</option>
						</select>
						<FormErrorMessage>{errors.backupStorageLocation}</FormErrorMessage>
					</FormControl>

					{/* Render Credential Fields Based on Selection */}
					{renderCredentialFields(formValues.backupStorageLocation)}

					{/* Buttons */}
					<Box
						mt={3}
						style={{ minHeight: "40px" }}
					>
						{!runningBackup && (
							<>
								<Button
									type="button"
									className={
										"button button-" +
										saveButtonColor +
										(savingSettings ? " disabled" : "")
									}
									onClick={handleSaveSettings}
									disabled={savingSettings}
								>
									{savingSettings ? (
										<>
											Saving... <span className="spinner is-active"></span>
										</>
									) : (
										"Save Settings"
									)}
								</Button>
								<Button
									type="button"
									className={
										"button button-" +
										saveAndBackupNowButtonColor +
										" " +
										(savingSettings ? " disabled" : "")
									}
									style={{ marginLeft: "10px" }}
									onClick={handleSaveAndBackupNow}
									disabled={savingSettings}
								>
									Save & Backup Now
								</Button>
							</>
						)}
						{/* Backup Progress */}
						{backupProgress && <BackupProgressBar {...backupProgress} />}
					</Box>
				</form>
			</Container>
		</div>
	);
};

SettingsForm.propTypes = {
	settings: PropTypes.shape({
		frequency: PropTypes.string,
		time: PropTypes.string,
		email: PropTypes.string,
		backupStorageLocation: PropTypes.string,
		nonce: PropTypes.string,
		backupStorageCredentials: PropTypes.object,
	}).isRequired,
	ajaxUrl: PropTypes.string.isRequired,
	nonce: PropTypes.string.isRequired,
};

export default SettingsForm;
