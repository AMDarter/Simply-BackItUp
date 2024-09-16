import React, { useState } from "react";
import { Button } from "@chakra-ui/react";
import { useAlert } from "../context/AlertContext";

const DownloadBackup = ({ ajaxUrl, nonce }) => {
	const [downloadingBackup, setDownloadingBackup] = useState(false);
	const { createAlert } = useAlert();

	const handleDownloadBackup = async () => {
		const formData = new FormData();
		formData.append("action", "simply_backitup_download_backup_zip");
		formData.append("nonce", nonce);

		try {
			const response = await fetch(ajaxUrl, {
				method: "POST",
				body: formData,
			});

			const contentType = response.headers.get("Content-Type") || null;

			if (contentType && contentType.includes("application/json")) {
                // Handle JSON response
				const json = await response.json();
				const message =
					json.data?.message ||
					json.message ||
					"An error occurred during backup download.";
				return { success: false, message };
			} else if (contentType && contentType.includes("application/zip")) {
				// Handle Blob response (file download)
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement("a");
				a.href = url;

				// Use the filename from Content-Disposition header or a default name
				const contentDisposition = response.headers.get("Content-Disposition");
				const filename = contentDisposition
					? contentDisposition.split("filename=")[1].trim()
					: "backup.zip";
				a.download = filename.replace(/"/g, ""); // Clean up any quotes
				document.body.appendChild(a);
				a.click();
				a.remove();

				return { success: true, message: "Download initiated." };
			}
		} catch (error) {
			return {
				success: false,
				message: error.message || "An error occurred during backup download.",
			};
		}
	};

	const handleDownloadBackupClick = async (e) => {
		e.preventDefault();

		setDownloadingBackup(true);

		const result = await handleDownloadBackup();

		console.log(result);

		if (result.success) {
			createAlert(result.message || "Downloading backup...", "success");
		} else {
			createAlert(result.message, "error");
		}
		setDownloadingBackup(false);
	};

	return (
		<div>
			<Button
				type="button"
				className={
					"button button-secondary" + (downloadingBackup ? " disabled" : "")
				}
				style={{ marginLeft: "10px" }}
				onClick={handleDownloadBackupClick}
				disabled={downloadingBackup}
			>
				{downloadingBackup ? (
					<>
						Downloading...{" "}
						<span
							className="spinner is-active"
							style={{ display: "inline-block" }}
						></span>
					</>
				) : (
					"Download Backup"
				)}
			</Button>
		</div>
	);
};

export default DownloadBackup;
