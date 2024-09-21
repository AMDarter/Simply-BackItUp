import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { ChakraProvider } from "@chakra-ui/react";
import App from "./App.jsx";
import "./index.css";
import { AjaxProvider } from "./context/AjaxContext";

console.log("SimplyBackItUp", SimplyBackItUp);

createRoot(document.getElementById("simply-backitup-root")).render(
	<StrictMode>
		<ChakraProvider>
			{!SimplyBackItUp && <p><strong>SimplyBackItUp</strong> is not properly configured.</p>}
			<AjaxProvider
				ajaxUrl={SimplyBackItUp.ajaxUrl}
				nonce={SimplyBackItUp.nonce}
			>
				<App />
			</AjaxProvider>
		</ChakraProvider>
	</StrictMode>
);
