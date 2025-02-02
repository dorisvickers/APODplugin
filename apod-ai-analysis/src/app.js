import React from 'react';
import { createRoot } from 'react-dom/client';
import { VertexAnalysis, KeywordAnalysis, SimilarImages } from './components';

// Main rendering function for analysis components
function renderAnalysisComponents(containerId, data) {
    const container = document.getElementById(containerId);
    if (container) {
        const root = createRoot(container);
        root.render(
            <div className="analysis-wrapper">
                <VertexAnalysis analysis={data.aiAnalysis} />
                <KeywordAnalysis {...data.contentAnalysis} />
                <SimilarImages similar={data.similarImages} />
            </div>
        );
    }
}

// jQuery initialization
jQuery(document).ready(function($) {
    let originalResults = [];
    
    function updateStatistics(data) {
        const statsHtml = `
            <div class="stats-overview">
                <p>Displaying: ${data.results.length} images</p>
                <p>Last Updated: ${data.lastUpdate}</p>
            </div>
        `;
        $('.apod-stats').html(statsHtml);
    }

    function updateResultsGrid(data) {
        const sortedResults = [...data.results].sort((a, b) => 
            new Date(b.date) - new Date(a.date)
        );
        
        const html = `
            <div class="apod-results-container">
                <div class="apod-controls">
                    <input type="text" class="apod-search" placeholder="Search images...">
                </div>
                <div class="apod-results-grid">
                    ${sortedResults.map(result => `
                        <div class="apod-result-card">
                            <div class="apod-thumbnail-wrapper">
                                <img src="${result.imageUrl}" alt="${result.title}" class="apod-thumbnail">
                            </div>
                            <div class="apod-content-wrapper">
                                <h3>
                                    <a href="https://apod.nasa.gov/apod/ap${result.date.replace(/-/g, '').slice(2)}.html" 
                                       target="_blank" class="apod-title-link">${result.title}</a>
                                </h3>
                                <p class="apod-date">Date: ${result.date}</p>
                                <div class="apod-explanation">
                                    <p class="explanation-preview">${result.explanation.substring(0, 150)}...</p>
                                    <div class="explanation-full" style="display: none;">
                                        ${result.explanation}
                                    </div>
                                    <button class="show-more-btn">Show More</button>
                                </div>
                                <div id="analysis-${result.date.replace(/-/g, '')}" class="analysis-container"></div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        $('.apod-content').find('.apod-results-container').remove();
        $('.apod-content').append(html);

        // Initialize components for each result
        sortedResults.forEach(result => {
            renderAnalysisComponents(
                `analysis-${result.date.replace(/-/g, '')}`,
                result
            );
        });

        // Add event handlers
        $('.show-more-btn').click(function() {
            const $btn = $(this);
            const $preview = $btn.siblings('.explanation-preview');
            const $full = $btn.siblings('.explanation-full');
            
            if ($full.is(':visible')) {
                $full.hide();
                $preview.show();
                $btn.text('Show More');
            } else {
                $full.show();
                $preview.hide();
                $btn.text('Show Less');
            }
        });

        // Add search functionality
        $('.apod-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterResults(searchTerm);
        });
    }

    function filterResults(searchTerm) {
        const filteredResults = originalResults.filter(result => 
            result.title.toLowerCase().includes(searchTerm) ||
            result.explanation.toLowerCase().includes(searchTerm)
        );

        updateResultsGrid({
            dateRange: 'Last 30 Days',
            totalImages: filteredResults.length,
            lastUpdate: originalResults.lastUpdate,
            results: filteredResults
        });
    }

    async function loadAPODAnalysis() {
        const container = $('.apod-analysis-container');
        const loading = container.find('.apod-loading');
        const error = container.find('.apod-error');
        const content = container.find('.apod-content');

        try {
            const response = await $.ajax({
                url: apodAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_apod_data',
                    nonce: apodAjax.nonce
                }
            });

            if (response.success && response.data) {
                originalResults = response.data.results;
                
                loading.hide();
                error.hide();
                content.show();
                
                updateStatistics(response.data);
                updateResultsGrid(response.data);
            } else {
                throw new Error('Invalid response format');
            }
        } catch (err) {
            console.error('Error:', err);
            loading.hide();
            content.hide();
            error.show().text('Error loading APOD data: ' + err.message);
        }
    }

    // Initialize the application
    loadAPODAnalysis();
});