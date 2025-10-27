jQuery(document).ready(function(){
    const ewtSubsubsubList = jQuery('.ewt_subsubsub');
    const ewtBulkTranslateBtn = jQuery('.ewt-bulk-translate-btn');

    if(ewtSubsubsubList.length){
        const $defaultSubsubsub = jQuery('ul.subsubsub:not(.ewt_subsubsub_list)');

        if($defaultSubsubsub.length){
            $defaultSubsubsub.before(ewtSubsubsubList);
            ewtSubsubsubList.show();
        }
    }

    if(ewtBulkTranslateBtn.length){
        const $defaultFilter = jQuery('#posts-filter .actions:not(.bulkactions)');
        const $bulkAction=jQuery('.actions.bulkactions');

        if($defaultFilter.length){
            $defaultFilter.each(function(){
                const clone=ewtBulkTranslateBtn.clone(true);
                jQuery(this).append(clone);
                clone.show();
            });

            ewtBulkTranslateBtn.remove();
        }else if($bulkAction.length){
            $bulkAction.each(function(){
                const clone=ewtBulkTranslateBtn.clone(true);
                jQuery(this).after(clone);
                clone.show();
            });
        }
    }
});
