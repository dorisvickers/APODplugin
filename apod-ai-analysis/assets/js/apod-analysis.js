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

   const categoryDescriptions = {
       'deep_space': 'Galaxies, nebulae, cosmic structures',
       'solar_system': 'Planets, moons, solar system objects',
       'star': 'Stars and stellar phenomena',
       'earth_sky': 'Atmospheric and near-Earth events',
       'technology': 'Space instruments and technology',
       'other': 'Other astronomical objects'
   };

   const categoryKeywords = {
       'deep_space': ['galaxy', 'nebula', 'cluster', 'deep space', 'cosmic', 'ngc', 'messier', 'forming', 'expansion', 'void', 'dark matter', 'dark energy', 'cosmic rays', 'supercluster', 'interstellar medium'],
       'solar_system': ['planet', 'mars', 'venus', 'jupiter', 'saturn', 'mercury', 'uranus', 'neptune', 'sun', 'moon', 'lunar', 'asteroid', 'comet', 'dwarf planet', 'Kuiper Belt', 'satellite'],
       'star': ['star', 'supernova', 'constellation', 'nova', 'stellar', 'supernovae', 'neutron star', 'black hole', 'red giant', 'white dwarf', 'main sequence', 'variable star', 'binary star', 'massive star', 'stellar evolution'],
       'earth_sky': ['aurora', 'meteor', 'eclipse', 'sky', 'atmosphere', 'weather', 'clouds', 'lightning', 'rainbow', 'pollution', 'horizon', 'twilight', 'sunset', 'daytime', 'night sky'],
       'technology': ['telescope', 'observatory', 'spacecraft', 'satellite', 'space station', 'probe', 'rover', 'lander', 'launch vehicle', 'space shuttle', 'radio telescope', 'spectrometer', 'orbiter', 'sensor', 'communication satellite']
   };

   function sortByDateDesc(a, b) {
       return Date.parse(b.date) - Date.parse(a.date);
   }

   function categorizeImage(title, explanation) {
       const text = (title + ' ' + explanation).toLowerCase();
       let maxConfidence = 0;
       let category = 'other';

       const titleWeight = 3;
       const descriptionWeight = 1;

       for (const [cat, words] of Object.entries(categoryKeywords)) {
           const titleMatches = words.filter(word => title.toLowerCase().includes(word));
           const descriptionMatches = words.filter(word => explanation.toLowerCase().includes(word));
           
           const confidence = Math.min(1, (
               (titleMatches.length * titleWeight + descriptionMatches.length * descriptionWeight) / 
               (Math.min(words.length, 5) * (titleWeight + descriptionWeight))
           ));

           if (confidence > maxConfidence) {
               maxConfidence = confidence;
               category = cat;
           }
       }

       return {
           category: category,
           confidence: maxConfidence || 0.3
       };
   }

   function findSimilarImages(target, allImages, count = 3) {
       return allImages
           .filter(img => img.date !== target.date)
           .map(img => {
               const targetCat = categorizeImage(target.title, target.explanation);
               const imgCat = categorizeImage(img.title, img.explanation);
               
               let matchScore = 0;
               if (targetCat.category === imgCat.category) {
                   matchScore += 50;
                   matchScore += Math.min(targetCat.confidence, imgCat.confidence) * 50;
               }
               
               const targetWords = new Set((target.title + ' ' + target.explanation).toLowerCase().split(/\W+/));
               const imgWords = new Set((img.title + ' ' + img.explanation).toLowerCase().split(/\W+/));
               const commonWords = [...targetWords].filter(word => imgWords.has(word));
               matchScore += (commonWords.length / Math.max(targetWords.size, imgWords.size)) * 25;

               return {...img, matchPercent: Math.round(matchScore)};
           })
           .sort((a, b) => b.matchPercent - a.matchPercent)
           .slice(0, count);
   }

   function updateStatistics(data) {
       const categoryStats = {};
       data.results.forEach(result => {
           const category = categorizeImage(result.title, result.explanation).category;
           categoryStats[category] = (categoryStats[category] || 0) + 1;
       });

       const statsHtml = `
           <div class="apod-stats">
               <div class="stats-grid">
                   <div class="stats-overview">
                       <p>Displaying: ${data.results.length} images</p>
                       <p>Updated: ${data.lastUpdate}</p>
                       <p>Similarity pool: ${data.allImages.length} images</p>
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

   function updateResultsGrid(data) {
       const sortedResults = [...data.results].sort(sortByDateDesc);
       
       const html = `
           <div class="apod-results-container">
               <div class="apod-controls">
                   <select class="apod-category-filter">
                       <option value="filter" disabled selected>Filter by Category</option>
                       <option value="all">Show All Categories</option>
                       <option value="divider" disabled>──────────</option>
                       ${Object.entries(categoryLabels).map(([key, name]) => 
                           `<option value="${key}">${name}</option>`
                       ).join('')}
                   </select>
                   <input type="text" class="apod-search" placeholder="Filter results...">
               </div>
               <div class="apod-results-grid">
                   ${sortedResults.map(result => {
                       const category = categorizeImage(result.title, result.explanation);
                       const similarImages = findSimilarImages(result, data.allImages);

                       return `
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
                                   <div class="apod-category">${categoryLabels[category.category]} (${(category.confidence * 100).toFixed(1)}% confidence)</div>
                                   <p class="apod-explanation">${result.explanation}</p>
                                   
                                   ${similarImages.length > 0 ? `
                                       <div class="apod-similar-images">
                                           <h4>Similar Images</h4>
                                           <div class="similar-images-grid">
                                               ${similarImages.map(similar => `
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
                       `;
                   }).join('')}
               </div>
           </div>
       `;
       
       $('.apod-content').find('.apod-results-container').remove();
       $('.apod-content').append(html);

       const search = $('.apod-search');
       const categoryFilter = $('.apod-category-filter');
       
       search.on('input', () => {
           filterResults(search.val(), categoryFilter.val());
       });
       
       categoryFilter.on('change', () => {
           filterResults(search.val(), categoryFilter.val());
       });
   }

   function filterResults(searchTerm, category) {
       let results = [...originalResults];
       
       if (category && category !== 'all' && category !== 'filter') {
           results = results.filter(result => 
               categorizeImage(result.title, result.explanation).category === category
           );
       }
       
       if (searchTerm) {
           const term = searchTerm.toLowerCase();
           results = results.filter(result => 
               result.title.toLowerCase().includes(term) ||
               result.explanation.toLowerCase().includes(term)
           );
       }

       results.sort(sortByDateDesc);

       updateResultsGrid({
           ...originalData,
           results: results
       });
       
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
               const sortedResults = [...response.data.results].sort(sortByDateDesc);
               
               originalResults = sortedResults;
               originalData = {
                   ...response.data,
                   results: sortedResults,
                   allImages: response.data.allImages
               };

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