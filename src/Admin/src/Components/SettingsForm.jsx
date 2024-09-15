import React, { useState, useEffect } from "react";
import PropTypes from "prop-types";
import BackupProgessBar from "./BackupProgessBar";
import {
	Box,
	Button,
	Container,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Input,
} from "@chakra-ui/react";

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
	const [alert, setAlert] = useState(null);
	const [backupProgress, setBackupProgress] = useState(null);
	const [alertTimeout, setAlertTimeout] = useState(null);
	const [lastBackupTime, setLastBackupTime] = useState(
		settings?.lastBackupTime || null
	);
	const [savingSettings, setSavingSettings] = useState(false);
	const [saveButtonColor, setSaveButtonColor] = useState("primary");
	const [scrollY, setScrollY] = useState(window.scrollY);

	useEffect(() => {
		const handScrollPositionY = (e) => {
			setScrollY(window.scrollY);
		};
		window.addEventListener("scroll", handScrollPositionY);
		return () => {
			window.removeEventListener("scroll", handScrollPositionY);
		};
	}, []);

	useEffect(() => {
		return () => {
			clearTimeout(alertTimeout);
		};
	}, []);

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
		console.log({ errors });
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

	const recursiveAppendFormData = (formData, key, data) => {
		if (Array.isArray(data)) {
			for (const [index, subData] of data.entries()) {
				recursiveAppendFormData(formData, `${key}[${index}]`, subData);
			}
		} else if (typeof data === "object" && data !== null) {
			for (const [subKey, subData] of Object.entries(data)) {
				recursiveAppendFormData(formData, `${key}[${subKey}]`, subData);
			}
		} else {
			formData.append(key, data);
		}
	};

	const handleSubmit = async (e) => {
		e.preventDefault();

		try {
			if (savingSettings) {
				throw new Error("Settings are being saved. Cannot save again.");
			}
			setSavingSettings(true);

			if (!validateForm()) {
				setSavingSettings(false);
				throw new Error(
					"The form contains errors. Please correct them and try again."
				);
			}

			const formData = new FormData();
			formData.append("action", "simply_backitup_save_settings");
			formData.append("nonce", nonce);

			for (const [key, value] of Object.entries(formValues)) {
				recursiveAppendFormData(formData, key, value);
			}

			const response = await fetch(ajaxUrl, {
				method: "POST",
				body: formData,
			});
			const responseData = await response.json();
			if (responseData?.success) {
				createAlert("Settings saved successfully.", "success");
				setSavingSettings(false);
				setSaveButtonColor("success");
				return responseData;
			} else {
				createAlert(
					"Failed to save settings: " +
						(responseData?.data?.message || "An unexpected error occurred."),
					"error"
				);
				if (responseData?.data?.validationErrors) {
					setErrors((prevErrors) => ({
						...prevErrors,
						...responseData.data.validationErrors,
					}));
				}
				setSavingSettings(false);
				setSaveButtonColor("error");
				return Promise.reject(responseData);
			}
		} catch (error) {
			console.error("Error:", error);
			createAlert(error.message, "error");
			setSavingSettings(false);
			setSaveButtonColor("error");
			return Promise.reject(error);
		} finally {
			setTimeout(() => {
				setSaveButtonColor("primary");
			}, 1000);
		}
	};

	const createAlert = (message, type = "success") => {
		setAlert({ message, type });
		clearTimeout(alertTimeout);
		setAlertTimeout(
			setTimeout(() => {
				setAlert(null);
			}, 5000)
		);
	};

	const performBackupStep = async ({
		action,
		nonce,
		progressValue,
		message,
	}) => {
		const formData = new FormData();
		formData.append("action", action);
		formData.append("nonce", nonce);

		const response = await fetch(ajaxUrl, {
			method: "POST",
			body: formData,
		});

		const responseData = await response.json();
		console.log({ responseData });
		if (responseData.success) {
			setBackupProgress({
				value: progressValue,
				message: responseData?.data?.message || message,
				failed: false,
			});
			if (progressValue === 100 && responseData?.data?.backupTime) {
				setLastBackupTime(responseData.data.backupTime);
			}
		} else {
			throw new Error(
				responseData?.data?.message || "Failed to perform backup step."
			);
		}
	};

	const handleBackupNow = async () => {
		setSavingSettings(true);
		setBackupProgress({
			value: 0,
			message: "Starting backup...",
		});

		try {
			await performBackupStep({
				action: "simply_backitup_step1",
				nonce: nonce,
				progressValue: 33,
				message: "Creating backup file...",
			});
			await performBackupStep({
				action: "simply_backitup_step2",
				nonce: nonce,
				progressValue: 66,
				message: "Uploading backup file...",
			});
			await performBackupStep({
				action: "simply_backitup_step3",
				nonce: nonce,
				progressValue: 100,
				message: "Backup completed.",
			});
			createAlert("Backup completed successfully.", "success");
			setTimeout(() => {
				setBackupProgress(null);
				setSavingSettings(false);
			}, 2000);
		} catch (error) {
			console.error("Error during backup process:", error);
			setBackupProgress(null);
			createAlert("An error occurred during backup.", "error");
			setSavingSettings(false);
		}
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
			{renderCredentialInput("Google Drive API Key", "googleDriveApiKey")}
			{renderCredentialInput("Google Drive Client ID", "googleDriveClientId")}
			{renderCredentialInput(
				"Google Drive Client Secret",
				"googleDriveClientSecret"
			)}
		</>
	);

	const renderDropboxFields = () => (
		<>{renderCredentialInput("Dropbox Access Token", "dropboxAccessToken")}</>
	);

	const renderOneDriveFields = () => (
		<>
			{renderCredentialInput("OneDrive Client ID", "oneDriveClientId")}
			{renderCredentialInput("OneDrive Client Secret", "oneDriveClientSecret")}
		</>
	);

	const renderAmazonS3Fields = () => (
		<>
			{renderCredentialInput("Amazon S3 Access Key", "amazonS3AccessKey")}
			{renderCredentialInput("Amazon S3 Secret Key", "amazonS3SecretKey")}
			{renderCredentialInput("Amazon S3 Bucket Name", "amazonS3BucketName")}
			{renderCredentialInput("Amazon S3 Region", "amazonS3Region")}
		</>
	);

	const renderFTPFields = () => (
		<>
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
				disabled={savingSettings}
				value={formValues.backupStorageCredentials[inputId]}
				onChange={handleCredentialChange}
				onBlur={() => clearErrorForField(inputId)}
			/>
			<FormErrorMessage>{errors[inputId]}</FormErrorMessage>
		</FormControl>
	);

	const render24HourTimeOptions = () => {
		return [...Array(24)].map((_, i) => (
			<option
				key={i}
				value={`${i.toString().padStart(2, "0")}:00`}
			>
				{i.toString().padStart(2, "0")}:00
			</option>
		));
	};

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

	const handleDownloadBackup = async (e) => {
		e.preventDefault();
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
				throw new Error("Something went wrong while downloading backup.");
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
		} catch (error) {
			console.error("Error downloading backup:", error);
			createAlert("Failed to download backup: " + error.message, "error");
		}
	};

    const handleSaveAndBackupNow = async (e) => {
        e.preventDefault();
        try {
            if (savingSettings) {
                throw new Error("Settings are being saved. Cannot save again.");
            }
            const response = await handleSubmit(e);
            if (response?.success) {
                await handleBackupNow();
            }
        } catch (error) {
            console.error("Error during backup process:", error);
            createAlert("An error occurred during backup.", "error");
            setSavingSettings(false);
        } finally {
            setSavingSettings(false);
        }
    };

	return (
		<div
			className="wrap"
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
				<h2 style={{ marginTop: "0px" }}>Backup Settings</h2>

				{alert && (
					<Box
						maxW="lg"
						style={{
							position: "absolute",
							top: `${scrollY}px`,
							left: "0",
							right: "0",
							zIndex: "100",
							margin: "0 auto",
							padding: "15px",
							boxShadow: "0 0 10px rgba(0,0,0,0.1)",
							backgroundColor: "#fff",
							borderRadius: "5px",
						}}
					>
						<div
							className={`notice notice-${alert.type}`}
							style={{
								margin: "0",
							}}
						>
							<p>{alert.message}</p>
						</div>
					</Box>
				)}

				{lastBackupTime && (
					<Box>
						<p>Last backup: {lastBackupTime}</p>
					</Box>
				)}

				{/* Download Backup Button */}
				<Button
					type="button"
					className={
						"button button-secondary" + (savingSettings ? " disabled" : "")
					}
					style={{ marginLeft: "10px" }}
					onClick={handleDownloadBackup}
					disabled={savingSettings}
				>
					Download Backup ZIP
				</Button>

				<form onSubmit={handleSubmit}>
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
							disabled={savingSettings}
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
							disabled={savingSettings}
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
							disabled={savingSettings}
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
							disabled={savingSettings}
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
					{!backupProgress && (
						<Box mt={3}>
							<Button
								type="submit"
								className={
									"button button-" +
									saveButtonColor +
									(savingSettings ? " disabled" : "")
								}
								disabled={savingSettings}
							>
								{savingSettings ? (
									<>
										Saving... <span class="spinner is-active"></span>
									</>
								) : (
									"Save Settings"
								)}
							</Button>
							<Button
								type="button"
								className={
									"button button-secondary" +
									(savingSettings ? " disabled" : "")
								}
								style={{ marginLeft: "10px" }}
								onClick={handleSaveAndBackupNow}
								disabled={savingSettings}
							>
								Save & Backup Now
							</Button>
						</Box>
					)}
				</form>

				{/* Backup Progress */}
				{backupProgress && <BackupProgessBar {...backupProgress} />}
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
