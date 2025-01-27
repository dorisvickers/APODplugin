jQuery(document).ready(function($) {
    let originalResults = [];
    let originalData = null;

    const categoryLabels = {
        'deep_space': 'Deep Space Objects',
        'solar_system': 'Solar System Objects', 
        'star': 'Stellar Objects',
        'earth_sky': 'Earth and Sky Phenomena',
        'technology': 'Astronomical Technology',
        'other': 'Other Objects'
    };

    const categoryKeywords = {
        'deep_space': ['galaxy', 'nebula', 'cluster', 'deep space', 'cosmic', 'ngc', 'messier', 'quasar', 'globular', 'interstellar', 'supermassive'],
        'solar_system': ['planet', 'mars', 'venus', 'jupiter', 'saturn', 'mercury', 'uranus', 'neptune', 'pluto', 'moon', 'lunar', 'asteroid', 'comet', 'solar system'],
        'star': ['star', 'supernova', 'constellation', 'nova', 'stellar', 'sirius', 'betelgeuse', 'dwarf', 'giant star', 'binary'],
        'earth_sky': ['aurora', 'meteor', 'eclipse', 'sky', 'atmosphere', 'rainbow', 'sunset', 'sunrise', 'clouds', 'lightning', 'atmospheric'],
        'technology': ['telescope', 'observatory', 'spacecraft', 'satellite', 'space station', 'hubble', 'webb', 'iss', 'rocket', 'mission'],
        'other': []
    };

    function determineCategory(title, explanation) {
        const text = (title + ' ' + explanation).toLowerCase();
        let maxMatches = 0;
        let bestCategory = 'other';
        let confidence = 0;

        for (const [category, keywords] of Object.entries(categoryKeywords)) {
            const matches = keywords.filter(keyword => text.includes(keyword)).length;
            if (matches > maxMatches) {
                maxMatches = matches;
                bestCategory = category;
                confidence = Math.min((matches / keywords.length) * 100, 100);
            }
        }
        return {
            category: bestCategory,
            confidence: confidence.toFixed(1)
        };
    }

    function findSimilarImages(target, allImages, count = 3) {
        return allImages
            .filter(img => img.date !== target.date)
            .map(img => {
                const targetCat = determineCategory(target.title, target.explanation).category;
                const imgCat = determineCategory(img.title, img.explanation).category;
                
                let matchScore = 0;
                if (targetCat === imgCat) {
                    matchScore += 50;
                }
                
                const targetWords = new Set((target.title + ' ' + target.explanation).toLowerCase().split(/\W+/));
                const imgWords = new Set((img.title + ' ' + img.explanation).toLowerCase().split(/\W+/));
                const commonWords = [...targetWords].filter(word => imgWords.has(word));
                matchScore += (commonWords.length / Math.max(targetWords.size, imgWords.size)) * 50;

                return {...img, matchPercent: Math.round(matchScore)};
            })
            .sort((a, b) => b.matchPercent - a.matchPercent)
            .slice(0, count);
    }

    function updateStatistics(data) {
        const categoryStats = {};
        data.results.forEach(result => {
            const categorization = determineCategory(result.title, result.explanation);
            categoryStats[categorization.category] = (categoryStats[categorization.category] || 0) + 1;
        });

        const statsHtml = `
            <div class="apod-stats">
                <div class="stats-grid">
                    <div class="stats-overview">
                        <p>Displaying: ${data.results.length} images</p>
                        <p>Updated: ${data.lastUpdate}</p>
                        <p>Analysis pool: ${data.allImages.length} images</p>
                    </div>
                    <div class="category-bars">
                        ${Object.entries(categoryStats).map(([category, count]) => `
                            <div class="category-bar-item">
                                <div class="category-label">
                                    <span class="category-name">${categoryLabels[category]}</span>
                                    <span class="category-count">${count}</span>
                                </div>
                                <div class="bar-wrapper">
                                    <div class="bar-fill" style="width: ${(count / data.results.length * 100).toFixed(1)}%">
                                        ${(count / data.results.length * 100).toFixed(1)}%
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        $('.apod-stats').html(statsHtml);
    }

    function renderAIExplanation(aiExplanations, containerId) {
        const container = $(`#${containerId}`);
        
        const tabsHtml = `
            <div class="ai-explanation-tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="basic">Basic</button>
                    <button class="tab-button" data-tab="detailed">Detailed</button>
                    <button class="tab-button" data-tab="technical">Technical</button>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" data-tab="basic">
                        ${aiExplanations.basic}
                    </div>
                    <div class="tab-pane" data-tab="detailed">
                        ${aiExplanations.detailed}
                    </div>
                    <div class="tab-pane" data-tab="technical">
                        ${aiExplanations.technical}
                    </div>
                </div>
            </div>
        `;
        
        container.html(tabsHtml);
        
        container.find('.tab-button').click(function() {
            const tab = $(this).data('tab');
            container.find('.tab-button').removeClass('active');
            container.find('.tab-pane').removeClass('active');
            $(this).addClass('active');
            container.find(`.tab-pane[data-tab="${tab}"]`).addClass('active');
        });
    }

    function updateResultsGrid(data, selectedCategory = 'all') {
        const sortedResults = [...data.results].sort((a, b) => new Date(b.date) - new Date(a.date));
        
        const html = `
            <div class="apod-results-container">
                <div class="apod-controls">
                    <select class="apod-category-filter">
                        <option value="all" ${selectedCategory === 'all' ? 'selected' : ''}>All Categories</option>
                        <option value="divider" disabled>──────────</option>
                        ${Object.entries(categoryLabels).map(([key, name]) => 
                            `<option value="${key}" ${selectedCategory === key ? 'selected' : ''}>${name}</option>`
                        ).join('')}
                    </select>
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
                                <div class="apod-category">
                                    ${(() => {
                                        const catResult = determineCategory(result.title, result.explanation);
                                        return `${categoryLabels[catResult.category]} (${catResult.confidence}% confidence)`;
                                    })()}
                                </div>
                                <p class="apod-explanation">
                                    ${result.explanation.substring(0, 150)}
                                    <span class="explanation-full" style="display: none;">
                                        ${result.explanation.substring(150)}
                                    </span>
                                    <button class="show-more-btn">Show More</button>
                                </p>
                                <div id="ai-explanation-${result.date.replace(/-/g, '')}" class="ai-explanation"></div>
                                <button class="explain-button" data-date="${result.date}">Get AI Explanation</button>
                                
                                ${findSimilarImages(result, data.allImages).length > 0 ? `
                                    <div class="apod-similar-images">
                                        <h4>Similar Images</h4>
                                        <div class="similar-images-grid">
                                            ${findSimilarImages(result, data.allImages).map(similar => `
                                                <div class="similar-image-card">
                                                    <a href="https://apod.nasa.gov/apod/ap${similar.date.replace(/-/g, '').slice(2)}.html" 
                                                       target="_blank">
                                                        <img src="${similar.imageUrl}" alt="${similar.title}">
                                                        <div class="similar-image-info">
                                                            <p class="similar-title">${similar.title}</p>
                                                            <p class="similar-date">${similar.date}</p>
                                                            <p class="match-percent">${similar.matchPercent}% match</p>
                                                        </div>
                                                    </a>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        $('.apod-content').find('.apod-results-container').remove();
        $('.apod-content').append(html);

        // Add event handlers
        $('.show-more-btn').click(function() {
            const $btn = $(this);
            const $fullText = $btn.siblings('.explanation-full');
            $fullText.toggle();
            $btn.text($fullText.is(':visible') ? 'Show Less' : 'Show More');
        });

        $('.explain-button').click(function() {
            const date = $(this).data('date');
            const result = sortedResults.find(r => r.date === date);
            if (result && result.aiExplanations) {
                renderAIExplanation(result.aiExplanations, `ai-explanation-${date.replace(/-/g, '')}`);
                $(this).hide();
            }
        });

        // Add search and filter handlers
        const $search = $('.apod-search');
        const $categoryFilter = $('.apod-category-filter');
        
        $search.on('input', function() {
            filterResults($(this).val(), $categoryFilter.val());
        });
        
        $categoryFilter.on('change', function() {
            filterResults($search.val(), $(this).val());
        });
    }

    function filterResults(searchTerm, category) {
        let results = [...originalResults]; // Start with a fresh copy of original results
        
        // Filter by category if not "all"
        if (category && category !== 'all' && category !== 'divider') {
            results = results.filter(result => 
                determineCategory(result.title, result.explanation).category === category
            );
        }
        
        // Apply search term filter if exists
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            results = results.filter(result => 
                result.title.toLowerCase().includes(term) ||
                result.explanation.toLowerCase().includes(term)
            );
        }

        // Update the display with filtered results
        updateResultsGrid({
            ...originalData,
            results: results
        }, category); // Pass the selected category
        
        updateStatistics({
            ...originalData,
            results: results
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
                originalData = response.data;

                loading.hide();
                error.hide();
                content.show();
                updateStatistics(originalData);
                updateResultsGrid(originalData);
            }
        } catch (err) {
            console.error('Error:', err);
            loading.hide();
            content.hide();
            error.show().text('Error: ' + err.message);
        }
    }

    loadAPODAnalysis();
});