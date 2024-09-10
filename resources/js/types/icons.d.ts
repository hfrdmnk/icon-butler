type IconStyle = {
    file_path: string;
    svg_content: string;
};

type IconType = {
    id: string;
    name: string;
};

type Icon = {
    id: number;
    name: string;
    explanation: string;
    iconData: {
        [key: string]: IconStyle;
    };
};
