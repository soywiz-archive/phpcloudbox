using System;
using System.Collections.Generic;
using System.Collections.Specialized;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace PhpCloudBoxClientLibrary
{
	public class PhpCloudBoxServer
	{
		string Url;
		string Username;
		string Password;

		public PhpCloudBoxServer(string Url, string Username, string Password)
		{
			PhpCloudBoxUtils.AllowInvalidCertificates();

			this.Url = Url;
			this.Username = Username;
			this.Password = Password;
		}

		public class Result<TType>
		{
			public string result;
			public TType data;
		}

		public class FileInfo
		{
			public string rowid;
			public string path;
			public string sha1;
			public string ctime;
			public string mtime;
			public string perms;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <returns></returns>
		async public Task<List<FileInfo>> GetFileListAsync()
		{
			var HttpClient = GetAuthorizedHttpClient();

			var JsonText = await HttpClient.GetStringAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "tree.get" },
				}
			));
			//Console.WriteLine(JsonText);
			var Result = JsonConvert.DeserializeObject<Result<List<FileInfo>>>(JsonText);
			return Result.data;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="Path"></param>
		/// <returns></returns>
		async public Task<bool> DeleteFileAsync(string Path)
		{
			var HttpClient = GetAuthorizedHttpClient();

			var JsonText = await HttpClient.GetStringAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "tree.remove" },
					{ "path", Path },
				}
			));

			return true;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="Path"></param>
		/// <returns></returns>
		async public Task<Stream> DownloadFileAsync(string Path)
		{
			var HttpClient = GetAuthorizedHttpClient();

			var Stream = await HttpClient.GetStreamAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "tree.file.get" },
					{ "path", Path },
				}
			));

			return Stream;
		}

		async public Task<bool> HasFileDataAsync(string Path)
		{
			var HttpClient = GetAuthorizedHttpClient();

			var Sha1 = await PhpCloudBoxUtils.Sha1FileAsync(Path);

			var JsonText = await HttpClient.GetStringAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "file.has" },
					{ "sha1", Sha1 },
				}
			));

			var Result = JsonConvert.DeserializeObject<Result<bool>>(JsonText);
			return Result.data;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="RemotePath"></param>
		/// <param name="LocalPath"></param>
		/// <returns></returns>
		async public Task DownloadFileToAsync(string RemotePath, string LocalPath)
		{
			using (var LocalStream = File.Open(LocalPath, FileMode.Create, FileAccess.Write, FileShare.None))
			{
				var Stream = await DownloadFileAsync(RemotePath);
				await Stream.CopyToAsync(LocalStream, 1 * 1024 * 1024);
			}
		}

		async public Task<bool> AddFileAsync(string RemoteFile, string sha1, DateTime ctime, DateTime mtime, string perms)
		{
			var HttpClient = GetAuthorizedHttpClient();

			var JsonText = await HttpClient.GetStringAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "tree.add" },
					{ "path", RemoteFile },
					{ "sha1", sha1 },
					{ "ctime", "0" },
					{ "mtime", "0" },
					{ "perms", "0777" },
				}
			));

			return true;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="RemoteFile"></param>
		/// <param name="LocalFile"></param>
		/// <returns></returns>
		async public Task<bool> AddFileAsync(string RemoteFile, string LocalFile)
		{
			var sha1 = await PhpCloudBoxUtils.Sha1FileAsync(LocalFile);
			return await AddFileAsync(RemoteFile, sha1, DateTime.UtcNow, DateTime.UtcNow, "0777");
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="RemoteFile"></param>
		/// <param name="LocalFile"></param>
		/// <returns></returns>
		async public Task<bool> AddAndUploadFileAsync(string RemoteFile, string LocalFile)
		{
			if (!(await HasFileDataAsync(LocalFile)))
			{
				await UploadFileDataAsync(LocalFile);
			}
			await AddFileAsync(RemoteFile, LocalFile);
			return true;
		}

		/// <summary>
		/// 
		/// </summary>
		/// <param name="LocalFile"></param>
		/// <returns></returns>
		async public Task UploadFileDataAsync(string LocalFile)
		{
			using (var LocalStream = File.OpenRead(LocalFile))
			{
				var HttpClient = GetAuthorizedHttpClient();
				var MultipartFormDataContent = new MultipartFormDataContent();
				var StreamContent = new StreamContent(LocalStream);
				StreamContent.Headers.ContentType = new MediaTypeHeaderValue("application/octet-stream");
				StreamContent.Headers.ContentDisposition = new ContentDispositionHeaderValue("form-data");
				StreamContent.Headers.ContentDisposition.Name = "file";
				StreamContent.Headers.ContentDisposition.FileName = Path.GetFileName(LocalFile);
				MultipartFormDataContent.Add(StreamContent);

				var Response = await HttpClient.PostAsync(this.Url + "/?" + PhpCloudBoxUtils.ToQueryString(new NameValueCollection()
				{
					{ "action", "file.upload" },
				}
				), MultipartFormDataContent);

				var ResponseText = await Response.Content.ReadAsStringAsync();
				Console.WriteLine(ResponseText);
			}
		}

		private HttpClient GetAuthorizedHttpClient()
		{
			var HttpClient = new HttpClient();
			//HttpClient.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("basic", "test:test");
			HttpClient.DefaultRequestHeaders.Add("Authorization", "Basic " + Convert.ToBase64String(System.Text.ASCIIEncoding.ASCII.GetBytes(string.Format("{0}:{1}", this.Username, this.Password))));

			return HttpClient;
		}
	}
}
