export default function HiddenIcon( {
	width = 24,
	height = 24,
	...props
}: React.SVGProps< SVGSVGElement > ) {
	return (
		<svg
			{ ...props }
			width={ width }
			height={ height }
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M13.7226 6.2125C13.1641 6.0766 12.5883 6 11.9999 6C8.10055 6 4.75407 9.36447 3.31899 11.0546C2.8507 11.6061 2.8507 12.3939 3.31899 12.9454C4.17896 13.9582 5.72533 15.5723 7.66574 16.7033L8.41572 15.4043C8.13761 15.242 7.86389 15.0655 7.59553 14.8776C6.25019 13.9359 5.15775 12.7905 4.48406 12C5.15775 11.2095 6.25019 10.0641 7.59553 9.12235C8.96667 8.16257 10.4775 7.5 11.9999 7.5C12.3118 7.5 12.6231 7.5278 12.9329 7.58027L13.7226 6.2125ZM12.3504 8.58923C12.2352 8.57753 12.1182 8.57153 11.9999 8.57153C10.1063 8.57153 8.57132 10.1066 8.57132 12.0001C8.57132 12.7505 8.81237 13.4445 9.22126 14.0091L10.1233 12.4467C10.0893 12.3034 10.0713 12.1538 10.0713 12.0001C10.0713 11.1266 10.652 10.3888 11.4484 10.1515L12.3504 8.58923ZM12.8092 10.2491L13.5611 8.94679C14.6697 9.51479 15.4285 10.6688 15.4285 12.0001C15.4285 13.8937 13.8934 15.4287 11.9999 15.4287C11.3128 15.4287 10.6729 15.2266 10.1364 14.8785L10.8883 13.5763C11.2025 13.7983 11.5859 13.9287 11.9999 13.9287C13.065 13.9287 13.9285 13.0652 13.9285 12.0001C13.9285 11.224 13.4701 10.555 12.8092 10.2491ZM9.51376 15.957C10.3246 16.2986 11.1605 16.5 11.9999 16.5C13.5223 16.5 15.0331 15.8374 16.4043 14.8776C17.7496 13.9359 18.842 12.7905 19.5157 12C18.842 11.2095 17.7496 10.0641 16.4043 9.12235C15.6875 8.62066 14.9327 8.20018 14.1579 7.91308L14.917 6.59839C17.5164 7.64275 19.6204 9.80575 20.6808 11.0546C21.1491 11.6061 21.1491 12.3939 20.6808 12.9454C19.2457 14.6355 15.8992 18 11.9999 18C10.8611 18 9.76945 17.713 8.7588 17.2646L9.51376 15.957Z"
				fill="currentColor"
			/>
			<rect
				x="16.0625"
				y="4.61377"
				width="1.22727"
				height="16"
				transform="rotate(30 16.0625 4.61377)"
				fill="currentColor"
			/>
		</svg>
	);
}
