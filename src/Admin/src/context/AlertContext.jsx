import React, { createContext, useContext, useState } from "react";
import { Box } from "@chakra-ui/react";
import useScrollY from "../hooks/useScrollY";
import useTimeoutManager from "../hooks/useTimeoutManager";

const AlertContext = createContext();

export const useAlert = () => useContext(AlertContext);

export const AlertProvider = ({ children }) => {
	const [alert, setAlert] = useState(null);
	const scrollY = useScrollY();
	const { set: setTimeout, clear: clearTimeout } = useTimeoutManager();

	const createAlert = (message, type = "success") => {
		setAlert({ message, type });

		clearTimeout();

		setTimeout(() => {
			setAlert(null);
		}, 5000);
	};

	const AlertComponent = () => {
		return (
			<Box
				style={{
					position: "absolute",
					top: `${scrollY}px`,
					left: "0",
					right: "0",
					zIndex: "100",
					margin: "0 auto",
					maxWidth: "600px",
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
		);
	};

	return (
		<AlertContext.Provider value={{ createAlert }}>
			{alert && (
				<div style={{ position: "relative" }}>
					<AlertComponent />
				</div>
			)}
			{children}
		</AlertContext.Provider>
	);
};
