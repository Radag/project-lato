module.exports = {
    entry: "./src/index.js",  //náš vstupní bod aplikace
    output: {
        filename: "./www/js/bundle.js"   //výstupní balík všech zdrojových kódů
    },
    module: { //sem budeme zanedlouho vkládat transformační moduly
        loaders: [
            {
              test: /\.js$/,    //Všechny soubory s koncovkou js...
              loader: 'babel-loader',  //prožeň babel-loaderem (integrace babelu a webpacku)
              query: {
                  presets: ['react', 'es2015'], //vybrané babel presety: https://babeljs.io/docs/plugins/#presets
                  plugins: ["transform-class-properties"] //vybrané pluginy https://babeljs.io/docs/plugins/#transform-plugins
              }
            }
        ]
    }
};