FROM nginx:alpine
# Copy the pre-compiled dist folder straight from your host
COPY ./dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]