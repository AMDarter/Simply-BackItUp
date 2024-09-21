import React, { createContext, useContext, useState, useMemo } from "react";
import { Center } from "@chakra-ui/react";
import useTimeoutManager from "../hooks/useTimeoutManager";

const AlertContext = createContext();

export const useAlert = () => useContext(AlertContext);

export const AlertProvider = ({ children }) => {
	const [alert, setAlert] = useState(null);
	const { set: setTimeout, clear: clearTimeout } = useTimeoutManager();

	const createAlert = (message, type = "success") => {
		setAlert({ message, type });

		clearTimeout();

		setTimeout(() => {
			setAlert(null);
		}, 5000);
	};

	const AlertComponent = () => {
		const borderColor = () => {
			if (alert.type === "success") {
				return "#38A169";
			} else if (alert.type === "error") {
				return "#E53E3E";
			}
			return "#CBD5E0";
		};

        const simplyBackitupRootWidth = document.getElementById("simply-backitup-root").offsetWidth;

		return (
			<div
				style={{
					position: "fixed",
					top: "50px",
					zIndex: "100",
					backgroundColor: "transparent",
				}}
			>
				<Center
					style={{
                        width: (simplyBackitupRootWidth - 40) + "px",
						minWidth: "50%",
                        maxWidth: "100%",
						zIndex: "100",
						padding: "8px",
                        marginLeft: "20px",
						boxShadow: "0 0 10px rgba(0,0,0,0.1)",
						borderRadius: "5px",
						backgroundColor: "#FFF",
						borderColor: borderColor(),
						borderWidth: "1px",
                        fontSize: "16px",
                        color: "#2D3748",
                        fontWeight: "500",
					}}
				>
					{alert.message}
				</Center>
			</div>
		);
	};

	return (
		<AlertContext.Provider value={{ createAlert }}>
			{alert && <AlertComponent />}
			{children}
		</AlertContext.Provider>
	);
};
