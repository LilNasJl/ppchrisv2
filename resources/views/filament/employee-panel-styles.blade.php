<style data-employee-panel-layout>
    .fi-main,
    .fi-main-ctn,
    .fi-page,
    .fi-page-content,
    .fi-ta,
    .fi-ta-ctn {
        width: 100%;
        max-width: none;
        min-width: 0;
    }

    .fi-ta-content {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .fi-ta-content > table {
        width: 100%;
    }

    @media (max-width: 768px) {
        .fi-ta-content > table {
            min-width: max-content;
        }
    }
</style>
