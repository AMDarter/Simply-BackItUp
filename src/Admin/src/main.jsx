import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { ChakraProvider } from "@chakra-ui/react";
import App from "./App.jsx";
import "./index.css";

console.log("SimplyBackItUp", SimplyBackItUp);

createRoot(document.getElementById("simply-backitup-settings")).render(
	<StrictMode>
		<ChakraProvider>
			<App data={SimplyBackItUp} />
		</ChakraProvider>
	</StrictMode>
);
