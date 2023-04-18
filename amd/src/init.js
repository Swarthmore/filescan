import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import Drawer from 'core/drawer';

export const init = (results) => {

    const context = {
        name: 'A11y Check',
        results: results
    };
    // This will call the function to load and render our template.
    Templates.renderForPromise('block_a11y_check/summary', context)

        // It returns a promise that needs to be resoved.
        .then(({html, js}) => {

            // Add the block to the drawer if it's visible
            if (Drawer.isVisible("#theme_boost-drawers-blocks")) {
                Templates.appendNodeContents(
                    Drawer.getDrawerRoot("#theme_boost-drawers-blocks"),
                    html,
                    js
                );
                alert('Added to block drawer');
            }

            // Here eventually I have my compiled template, and any javascript that it generated.
            // The templates object has append, prepend and replace functions.
            // Templates.appendNodeContents('#page-content', html, js);
            // alert('Done');
        })

        // Deal with this exception (Using core/notify exception function is recommended).
        .catch((error) => displayException(error));
};