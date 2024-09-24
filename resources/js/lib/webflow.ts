export const prepareWebflowObject = (svgContent: string) => {
    const escapedSvg = svgContent.replace(/"/g, '"');
    const id = `webflow-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    return JSON.stringify({
        type: '@webflow/XscpData',
        payload: {
            nodes: [
                {
                    type: 'HtmlEmbed',
                    tag: 'div',
                    classes: [],
                    children: [],
                    v: escapedSvg,
                    data: {
                        search: { exclude: true },
                        embed: {
                            meta: {
                                html: escapedSvg,
                                div: false,
                                script: false,
                                compilable: false,
                                iframe: false,
                            },
                            type: 'html',
                        },
                        insideRTE: false,
                    },
                },
            ],
            styles: [],
            assets: [],
            ix1: [],
            ix2: {
                interactions: [],
                events: [],
                actionLists: [],
            },
        },
        meta: {
            unlinkedSymbolCount: 0,
            droppedLinks: 0,
            dynBindRemovedCount: 0,
            dynListBindRemovedCount: 0,
            paginationRemovedCount: 0,
        },
    });
};
